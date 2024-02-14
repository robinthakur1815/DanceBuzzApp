<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Enums\DiscussionTopicEnum;
use App\Jobs\ContactEmail;
use App\Jobs\QuoteEmail;
use App\Jobs\SubscriberMail;
use App\Quote;
use App\SoftDeletes;
use App\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StoriesExport;
use Validator;
use DB;


class ContactController extends Controller
{
    public function discussionTopics()
    {
        $topics = DiscussionTopicEnum::toArray();
        $arr = [];
        foreach ($topics as $key => $topic) {
            $temp = str_replace('_', ' ', $key);

            $arr[] = [$temp => $topic];
        }
        $result = Arr::collapse($arr);

        return ['topics' => $topics, 'filteredTopics' => $result];
    }

    public function saveSubscribers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email|max:255|unique:subscribers',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 400);
        }

        $data = [
            'email'     => $request->email,
            'is_active' => true,
        ];

        $subscribers = Subscriber::create($data);
        SubscriberMail::dispatch($subscribers);

        return response(['message' => 'Success', 'status' => true, 'message' => ''], 200);
    }

    public function saveContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|max:255',
            'email'     => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
      
        if($request->country_name){
            $country  = DB::connection('partner_mysql')->table('countries')->where('name',$request->country_name)->first();
           }
        $data = [
            'email'   => $request->email,
            'name'    => $request->name,
            'message' => $request->msg,
            'phone'   => $request->phone,
            'country_id' => $country->id,
        ];

        $contact = Contact::create($data);
        ContactEmail::dispatch($contact);

        return response(['message' => 'Success', 'status' => true, 'message' => ''], 200);
    }

    public function saveQuote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|max:255',
            'email'     => 'required|email|max:255',
            'phone'     => 'required|digits:10',
            'service'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
        $data = [
            'email'    => $request->email,
            'name'     => $request->name,
            'phone'    => $request->phone,
            'topic_id' => $request->service,
        ];

        $quote = Quote::create($data);
        QuoteEmail::dispatch($quote);

        return response(['message' => 'Success', 'status' => true, 'message' => ''], 200);
    }

    public function getAllSubscribers(Request $request)
    {
        $users = Subscriber::latest();

        if ($request->search) {
            $users = $users->where('email', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $users = $users->onlyTrashed();
        }
        if (isset($request->status)) {
            $users = $users->where('is_active', [$request->status]); 
          }
        if ($request->isActive) {
            $users = $users->where('is_active', $request->isActive);
        }
        if ($request->maxRows) {
            $users = $users->paginate($request->maxRows);
        } else {
            $users = $users->paginate(25);
        }

        return $users;
    }

    public function getAllContactQuiries(Request $request)
    {
        $quiries = Contact::latest();

        if ($request->search) {
            $quiries = $quiries
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $quiries = $quiries->onlyTrashed();
        }
        if ($request->maxRows) {
            $quiries = $quiries->paginate($request->maxRows);
        } else {
            $quiries = $quiries->get();
        }
        foreach($quiries as $query){
           if($query->country_id){
            $country = DB::connection('partner_mysql')->table('countries')->where('id', $query->country_id)->first();
            $query->country_name = $country->name;
            $query->country_code = $country->phonecode;
           }
        }

        return $quiries;
    }

    public function getAllQuoteDetails(Request $request)
    {
        $quotes = Quote::latest();

        if ($request->search) {
            $quotes = $quotes
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $quotes = $quotes->onlyTrashed();
        }
        if ($request->topicId) {
            $quotes = $quotes->where('topic_id', $request->topicId);
        }
        if ($request->maxRows) {
            $quotes = $quotes->paginate($request->maxRows);
        } else {
            $quotes = $quotes->get();
        }
        // foreach($quotes as $quote){
        //     $id =  DiscussionTopicEnum::getValue($quote->topic_id);
        //     $quote->topic_id = $id ;
        //     $quote['value_id'] = $id ;
        // }
        //
        // //    dd($id);
        //
        return $quotes;
    }

    public function deleteSubscribers(Request $request)
    {
        $user = auth()->user();
        $subscriberIds = $request->subscriberIds;
        foreach ($subscriberIds as $id) {
            $subscriber = Subscriber::find($id);
            if (! $subscriber) {
                return response(['errors' =>  ['Subscriber not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('delete', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $subscriber->delete();
        }

        return response(['message' =>  'Subscribers deleted successfully', 'status' => false], 200);
    }

    public function restoreSubscribers(Request $request)
    {
        $user = auth()->user();
        $subscriberIds = $request->subscriberIds;
        foreach ($subscriberIds as $id) {
            $subscriber = Subscriber::withTrashed()->find($id);
            if (! $subscriber) {
                return response(['errors' =>  ['Subscriber not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('restore', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $subscriber->restore();
        }

        return response(['message' =>  'Subscribers restored successfully', 'status' => false], 200);
    }

    public function updateSubscriberStatus(Request $request)
    {
        // $user = auth()->user();
        $subscriberId = $request->subscriberId;

        $requestedSubscriber = Subscriber::find($subscriberId);
        if (! $requestedSubscriber) {
            return response(['errors' =>  ['Subscriber not Found'], 'status' => false, 'message' => ''], 422);
        }
        // if (!$user->can('update', $requestedUser)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }
        $requestedSubscriber->update(['is_active' => $request->status]);
        $requestedSubscriber->refresh();

        return $requestedSubscriber;
    }

    public function downloadSubscribers(Request $request)
    {
        $subscribers = Subscriber::latest();
       // $status = $request['status'];
        
        if (isset($request['start_date']) and $request['start_date'] || isset($request['end_date']) and $request['end_date']) {
            $subscribers = $subscribers->whereBetween('created_at', [$request['start_date'], $request['end_date']]);
        }

        if (isset($request->status)) {
            $subscribers = $subscribers->where('is_active', [$request->status]);
            
          }
       //if($status < 2){
       //     $subscribers = $subscribers->where('is_active', $status);     
       // }
        $subscribers = $subscribers->get();
       
        $headers = [
           // 'id',
            'Email',
          //  'Status',
            'Subscribed On',
        ];

        $bodies = [];
        foreach ($subscribers as $data) {
            info($data);
            $body = [
              //  $data->id,
                $data->email,
               // $data->is_active,
                $data->created_at->format('d/m/Y h:i A'),
            ];
            array_push($bodies, $body);
        }

        return Excel::download(new StoriesExport($headers, $bodies), 'subscribers.csv');
    }

    public function restoreQuote(Request $request)
    {
        $quoteIds = $request->quoteIds;
        foreach ($quoteIds as $id) {
            $quote = Quote::withTrashed()->find($id);

            if (! $quote) {
                return response(['errors' =>  ['quotes not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('restore', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $quote->restore();
        }

        return response(['message' =>  'quotes restored successfully', 'status' => false], 200);
    }

    public function deleteQuote(Request $request)
    {
        $quoteIds = $request->quoteIds;
        foreach ($quoteIds as $id) {
            $quote = Quote::find($id);
            if (! $quote) {
                return response(['errors' =>  ['quote not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('delete', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $quote->delete();
        }

        return response(['message' =>  'quotes deleted successfully', 'status' => false], 200);
    }

    public function restoreContacts(Request $request)
    {
        $contactIds = $request->contactIds;
        foreach ($contactIds as $id) {
            $contact = Contact::withTrashed()->find($id);
            if (! $contact) {
                return response(['errors' =>  ['contacts not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('restore', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $contact->restore();
        }

        return response(['message' =>  'contact restored successfully', 'status' => false], 200);
    }

    public function deleteContact(Request $request)
    {
        $contactIds = $request->contactIds;
        foreach ($contactIds as $id) {
            $contact = Contact::find($id);
            if (! $contact) {
                return response(['errors' =>  ['contact not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('delete', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $contact->delete();
        }

        return response(['message' =>  'contacts deleted successfully', 'status' => false], 200);
    }

    public function downloadContactUs(Request $request)
    {
        $contacts = Contact::latest();
        
        if (isset($request['start_date']) and $request['start_date'] || isset($request['end_date']) and $request['end_date']) {
            $contacts = $contacts->whereBetween('created_at', [$request['start_date'], $request['end_date']]);
        }
        if ($request->search) {
            $contacts = $contacts
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $contacts = $contacts->onlyTrashed();
        }

        $contacts = $contacts->get();
       
        $headers = [
           // 'id',
            'Name',
            'Email',
            'Phone',
            'Message',
            'Submission Date',
        ];

        $bodies = [];
        foreach ($contacts as $data) {
            $body = [
               // $data->id,
                $data->name,
                $data->email,
                $data->phone,
                $data->message,
                $data->created_at->format('d/m/Y h:i A'),
            ];
            array_push($bodies, $body);
        }

        return Excel::download(new StoriesExport($headers, $bodies), 'ContactUs.csv');
    }
}
