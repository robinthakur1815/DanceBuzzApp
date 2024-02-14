<?php

namespace App\Http\Controllers;

use App\Enums\Gender;
use App\Enums\MediaType;
use App\Enums\RoleType;
use App\Enums\SiteType;
use App\Guardian;
use App\Helpers\CodeHelper;
use App\Helpers\HostUri;
use App\Helpers\MediaHelper;
use App\Helpers\SlugHelper;
use App\Http\Resources\UserCollection;

// use Validator;
use App\Media;
use App\Notifications\UserRegisterMail;
use App\PageView;
use App\Student;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use League\Csv\Writer;
use Log;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $users = User::with('avatarMediable.media')->where('id', '!=', auth()->id())->where('app_type', SiteType::CMS)->latest();

        if ($request->search) {
            $users = $users->where('name', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $users = $users->onlyTrashed();
        }
        if ($request->isActive) {
            $users = $users->where('is_active', $request->isActive);
        }
        if ($request->maxRows) {
            $users = $users->paginate($request->maxRows);
        } else {
            $users = $users->get();
        }

        return new UserCollection($users);
    }

    public function getUserAvatars(Request $request)
    {
        $user = auth()->user();

        $user->load('avatar');
        $media = $user->avatar;
        foreach ($media as $pic) {
            $pic->url = Storage::disk('s3')->url($pic->url);
        }

        return $media;
    }

    public function updateAvatar(Request $request)
    {
        $user = auth()->user();
        $slugHelper = new SlugHelper();

        $docName = $slugHelper->slugify(explode('.', $request['name'])[0]);
        if (!$docName) {
            $docName = \Str::random(5);
        }
        $mediaData = [
            'url' => $request['data'],
            'name' => $request['name'],
            'base_path' => sprintf(config('app.user_profile_path'), $user->id) . '/' . $docName,
        ];

        $mediaHelper = new MediaHelper();
        $media_path = $mediaHelper->saveMedia($mediaData);
        $mediaData = [
            'url' => $media_path,
            'name' => $request['name'],
            'updated_by' => $user->id,
            'created_by' => $user->id,
            'mime_type' => $request['type'],
            'size' => $request['size'],
            'media_type' => MediaType::UserMedia,
        ];
        $media = Media::create($mediaData);

        $user = $user->load('avatarMediable');
        // if ($user->avatarMediable) {
        $user->avatarMediable()->delete();
        // }
        $user->avatarMediable()->create(['media_id' => $media->id, 'name' => $media->name, 'created_by' => $user->id, 'updated_by' => $user->id]);

        return $media;
    }

    public function saveUserDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'name' => 'required|string',
                'phone' => 'required|integer',
                'role_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $email = User::where('email', $request->email)->where('app_type', 1)->first();
            if ($email) {
                return response(['errors' => ['email' => ['email already taken']], 'status' => false, 'message' => ''], 422);
            }

            $email = User::where('phone', $request->phone)->where('app_type', 1)->first();
            if ($email) {
                return response(['errors' => ['phone' => ['phone already taken']], 'status' => false, 'message' => ''], 422);
            }
            $codeHelper = new CodeHelper();
            $password = $codeHelper->userPassword();
            $token = $codeHelper->userToken();

            $data = [
                'email' => $request->email,
                'name' => $request->name,
                'phone' => $request->phone,
                'role_id' => $request->role_id,
                'is_active' => $request->is_active,
                'password' => bcrypt($password),
                'email_verified_at' => Carbon::now(),
                'app_type' => 1, // one for cms site id
            ];
            $user = User::create($data);

            $uri_fun = new HostUri();
            $url = $uri_fun->hostUrl() . '/login';

            // $user->notify((new EmailVerificationMail($user, $password, $token)));
            // NewUserRegistered::dispatch($user, $password, $url);

            $user->notify((new UserRegisterMail($user, $password, $url)));

            return $user;
        } catch (\Exception$e) {
            Log::error($e);

            return response(['message' => 'server error', 'status' => false], 500);
        }
    }

    public function updateUserStatus(Request $request)
    {
        $user = auth()->user();
        $userId = $request->userId;

        $requestedUser = User::find($userId);
        if (!$requestedUser) {
            return response(['errors' => ['User not Found'], 'status' => false, 'message' => ''], 422);
        }
        // if (!$user->can('update', $requestedUser)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }
        $requestedUser->update(['is_active' => $request->status]);
        $requestedUser->refresh();

        return $requestedUser;
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response(['errors' => ['User not Found'], 'status' => false, 'message' => ''], 422);
        }

        return $user;
    }

    public function updateUserDetails(Request $request, $id)
    {
        $user = User::find($id);
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'required|string',
            'phone' => 'required|integer',
            'role_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }

        $emailExist = User::where('email', $request->email)->where('app_type', 1)->first();
        if ($emailExist) {
            return response(['errors' => ['email' => ['The email is already registered with another role.']], 'status' => false, 'message' => ''], 422);
        }

        $existPhone = User::where('phone', $request->phone)->where('app_type', 1)->first();
        if ($existPhone) {
            return response(['errors' => ['phone' => ['Phone number already registered with another role.']], 'status' => false, 'message' => ''], 422);
        }

        $data = [
            'name' => request('name'),
            'email' => request('email'),
            'phone' => request('phone'),
            'role_id' => request('role_id'),
            'is_active' => request('is_active'),
        ];
        $user->update($data);

        return $user;
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();
    }

    public function deleteUsers(Request $request)
    {
        $user = auth()->user();
        $userIds = $request->userIds;
        foreach ($userIds as $id) {
            $user = User::find($id);
            if (!$user) {
                return response(['errors' => ['User not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('delete', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $user->delete();
        }

        return response(['message' => 'Users deleted successfully', 'status' => false], 200);
    }

    public function restoreUsers(Request $request)
    {
        $user = auth()->user();
        $userIds = $request->userIds;
        foreach ($userIds as $id) {
            $user = User::withTrashed()->find($id);
            if (!$user) {
                return response(['errors' => ['User not Found'], 'status' => false, 'message' => ''], 422);
            }
            // if (!$user->can('restore', $user)) {
            //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
            // }
            $user->restore();
        }

        return response(['message' => 'Users restored successfully', 'status' => false], 200);
    }

    public function getUsersListing(Request $request)
    {
        $users = User::whereIn('role_id', [RoleType::Guardian, RoleType::Student])->where('app_type', SiteType::Partner)->latest();

        if ($request->search) {
            $users = $users->where('name', 'like', "%{$request->search}%");
        }

        if ($request->role_id) {
            $users = $users->where('role_id', $request->role_id);
        }

        /*  $users = $users->get();

        foreach ($users as $user) {
        if ($user->role_id == RoleType::Guardian) {
        $guardian = Guardian::where('user_id', $user->id)->first();
        if($guardian){
        $user['link'] = $guardian['id'];
        }else{
        $user['link'] = "";
        }

        } else {
        $student = Student::where('user_id', $user->id)->first();
        if($student){
        $user['link'] = $student['id'];
        }else{
        $user['link'] = "";
        }
        }
        } */
        // $users = $users->where('link','!=',"");
        // $users = collect($users);
        $pageSize = $request->maxRows;

        $users = $users->paginate($pageSize);

        $usersData = $users->getCollection()->transform(function ($user) use ($request) {
            if ($user->role_id == RoleType::Guardian) {
                $guardian = Guardian::where('user_id', $user->id)->first();
                if ($guardian) {
                    $user['link'] = $guardian['id'];
                } else {
                    $user['link'] = "";
                }

            } else {
                $student = Student::where('user_id', $user->id)->first();
                if ($student) {
                    $user['link'] = $student['id'];
                } else {
                    $user['link'] = "";
                }
            }

            return $user;

        });

        $usersPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $usersData,
            $users->total(),
            $users->perPage(),
            $users->currentPage(), [
                'path' => \Request::url(),
                'query' => [
                    'page' => $users->currentPage(),
                ],
            ]
        );

        return new UserCollection($usersPaginated);
    }

    public function getUsersListingCsv(Request $request)
    {

        $headers = [
            'Name',
            'email',
            'username',
            'phone',
            'role',
            'created_at',
            'grade',
            'section',
            'gender',
            'address',
            'city',
            'zipcode',
            'guardians name',
            'guardians username',
            'guardians email',
            'guardians phone',
            'school_name',
            'school_email',
            'school_phone',
            'school_address',
            'registration_code',
            'registration_date ',

        ];
        $bodies = [];
        $tmpPath = public_path() . config('app.csv_report_path');
        if (!file_exists($tmpPath)) {
            mkdir($tmpPath);
        }

        $csvWriter = Writer::createFromPath($tmpPath . "/user.csv", 'w');
        $csvWriter->insertOne($headers);

        /*    $csvWriter = Writer::createFromPath($tmpPath . "/user.csv", 'w');
        $headers = [
        "Name",
        "Email",
        "Phone",
        "Gender",
        "Difficulty level",
        "Age Segment",
        "Preferred language",
        "Code",
        "Registration Date",
        "User Type",
        "Master OTP"

        ];
        $csvWriter->insertOne($headers); */
        set_time_limit(0);
        User::whereIn('role_id', [RoleType::Guardian, RoleType::Student])->where('app_type', SiteType::Partner)->chunk(1000, function ($users) use ($request, &$bodies, $csvWriter) {

            if ($request->search) {
                $users = $users->where('name', 'like', "%{$request->search}%");
            }

            if ($request->role_id) {
                $users = $users->where('role_id', $request->role_id);
            }
            if (isset($request['start_date']) and $request['start_date'] || isset($request['end_date']) and $request['end_date']) {
                $users = $users->whereBetween('created_at', [$request['start_date'], $request['end_date']]);
            }

            foreach ($users as $user) {
                if ($user->role_id == RoleType::Guardian) {
                    $guardian = Guardian::with('user', 'students.school')->where('user_id', $user->id)->first();
                    $user->data = $guardian;
                    //return $user;
                } else {
                    $student = Student::with('guardians.user', 'school', 'registrations')->where('user_id', $user->id)->first();
                    $user->data = $student;
                }
            }
            foreach ($users as $data) {
                $studentInfo = isset($data['data']['meta']) ? json_decode($data['data']['meta'], true) : '';
                $grade = $studentInfo && $studentInfo['grades'] ? $studentInfo['grades'] : "Not Available";
                $section = $studentInfo && $studentInfo['section'] ? $studentInfo['section'] : "Not Available";
                $gender = isset($data['data']['gender']) ? $data['data']['gender'] : "N/A";
                $guardians = isset($data['data']['guardians']) ? $data['data']['guardians'] : [];

                foreach ($guardians as $gua) {
                    $record = $this->createUsersCSVRecord($data, $gua, $grade, $section, $gender);
                    if ($record) {
                        $csvWriter->insertOne($record);

                    }

                }

                $students = isset($data['data']['students']) ? $data['data']['students'] : [];

                foreach ($students as $stud) {
                    if (!is_bool($stud)) {
                        info($stud);
                        $record = $this->createUsersCSVRecordStudent($stud, $grade, $section, $gender);
                        if ($record) {

                            $csvWriter->insertOne($record);

                        }

                    }

                }

            }

        });

        $data = ($tmpPath . "/user.csv");

        $headers = ['Content-Type: text/csv'];
        return response()->download($data, "user.csv", $headers)->deleteFileAfterSend(true);;

        /*  return Excel::download(new StoriesExport($headers, $bodies), 'User.csv'); */

    }

    public function createUsersCSVRecord($data, $gua, $grade, $section, $gender)
    {
        return [
            $data->name,
            $data->email,
            $data->username,
            $data->phone,
            RoleType::getKey($data->role_id),
            $data->created_at,
            $grade,
            $section,
            Gender::getKey($gender),
            $data && isset($data['data']['address']) ? $data['data']['address'] : "N/A",
            $data && isset($data['data']['city']) ? $data['data']['city'] : "N/A",
            $data && isset($data['data']['zipcode']) ? $data['data']['zipcode'] : "N/A",
            // $data && isset($data['data']['state_id']) ? $data['data']['state_id'] : "N/A",
            // $data && isset($data['data']['country_id']) ? $data['data']['country_id'] : "N/A",
            //   $data && isset($data['data']['school_id']) ? $data['data']['school_id'] : "N/A",
            $gua && isset($gua['user']['name']) ? $gua['user']['name'] : "N/A",
            $gua && isset($gua['user']['username']) ? $gua['user']['username'] : "N/A",
            $gua && isset($gua['user']['email']) ? $gua['user']['email'] : "N/A",
            $gua && isset($gua['user']['phone']) ? $gua['user']['phone'] : "N/A",
            $data && isset($data['data']['school']['name']) ? $data['data']['school']['name'] : "N/A",
            $data && isset($data['data']['school']['school_email']) ? $data['data']['school']['school_email'] : "N/A",
            $data && isset($data['data']['school']['school_phone']) ? $data['data']['school']['school_phone'] : "N/A",
            $data && isset($data['data']['school']['address']) ? $data['data']['school']['address'] : "N/A",
            $data && isset($data['data']['registrations']['registration_code']) ? $data['data']['registrations']['registration_code'] : "N/A",
            $data && isset($data['data']['registrations']['created_at']) ? $data['data']['registrations']['created_at'] : "N/A",
        ];
    }

    public function createUsersCSVRecordStudent($data, $grade, $section, $gender)
    {

        return [
            $data->name,
            $data->email,
            $data->username,
            $data->phone,
            RoleType::getKey($data->role_id),
            $data->created_at,
            $grade,
            $section,
            Gender::getKey($gender),
            $data && isset($data['data']['address']) ? $data['data']['address'] : "N/A",
            $data && isset($data['data']['city']) ? $data['data']['city'] : "N/A",
            $data && isset($data['data']['zipcode']) ? $data['data']['zipcode'] : "N/A",
            // $data && isset($data['data']['state_id']) ? $data['data']['state_id'] : "N/A",
            // $data && isset($data['data']['country_id']) ? $data['data']['country_id'] : "N/A",
            //   $data && isset($data['data']['school_id']) ? $data['data']['school_id'] : "N/A",
            // $gua && isset($gua['user']['name']) ? $gua['user']['name'] : "N/A",
            // $gua && isset($gua['user']['username']) ? $gua['user']['username'] : "N/A",
            // $gua && isset($gua['user']['email']) ? $gua['user']['email'] : "N/A",
            // $gua && isset($gua['user']['phone']) ? $gua['user']['phone'] : "N/A",
            $data && isset($data['data']['school']['name']) ? $data['data']['school']['name'] : "N/A",
            $data && isset($data['data']['school']['school_email']) ? $data['data']['school']['school_email'] : "N/A",
            $data && isset($data['data']['school']['school_phone']) ? $data['data']['school']['school_phone'] : "N/A",
            $data && isset($data['data']['school']['address']) ? $data['data']['school']['address'] : "N/A",
            $data && isset($data['data']['registrations']['registration_code']) ? $data['data']['registrations']['registration_code'] : "N/A",
            $data && isset($data['data']['registrations']['created_at']) ? $data['data']['registrations']['created_at'] : "N/A",
            // $gua,
            // $stud,
            //$user,
        ];
    }

    public function saveViewCount(Request $request)
    {
        $sh = PageView::where('user_type', $request->user_type)->where('page_type', $request->page_type)->first();

        if ($sh != null) {
            $sh->increment('count');
        } else {
            PageView::create([
                'user_type' => $request->user_type,
                'count' => 1,
                'page_type' => $request->page_type,
            ]);
        }
        return response(['message' => 'User count successfully', 'status' => true], 200);

    }
    public function ViewCount($page_type)
    {
        $nonloginuser = PageView::where('user_type', 1)->where('page_type', $page_type)->sum('count');
        $loginuser = PageView::where('user_type', 2)->where('page_type', $page_type)->sum('count');
        return [
            'nonLogin' => $nonloginuser,
            'Login' => $loginuser,
        ];
    }

}
