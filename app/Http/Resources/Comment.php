<?php

namespace App\Http\Resources;

use App\Enums\RoleType;
use App\Http\Resources\CommentCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;


class Comment extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = null;
        if (auth()->user()) {
            $user = auth()->user();
        }

        $firsLast = '';
        $userEmail = '';
        $name = '';
        $userAvatar = '';
        $isMe = false;

        if ($this->user and isset($this->user->name[0])) {
            $firsLast = Str::title($this->user->name[0]);
        }
        if ($this->user) {
            $userEmail = $this->user->email;
            $name = Str::title($this->user->name);
        }

        if ($user) {
            $isMe = $this->created_by == $user->id ? true : false;
        }
        if($this->user->role_id == RoleType::SuperAdmin){
            $this->user->load('avatarMediable');
            if (! $this->user->avatarMediable || ! $this->user->avatarMediable->media) {
                $userAvatar = null;
            } else {
                $userAvatar = Storage::disk('s3')->url($this->user->avatarMediable->media->url);
            }
        }
        else  {
            if($this->user && $this->user->partnerAvatar){
                $userAvatar = $this->user->partnerAvatar;
            }
        }
        
        $parent_comment = Comment::where('parent_comment_id',$this->id)
         ->where('is_active', true)
        ->with('user')
        ->latest()
        ->get();
        
        $data = [
            'commentId'     => $this->id,
            'comment'       => $this->comment,
            'userId'        => $this->created_by,
            'post_id'       => $this->commentable_id,
            'reply_comment' => new CommentCollection($parent_comment),
            'userFirstLast' => $firsLast,
            'userEmail'     => $userEmail,
            'is_publish'     => $this->is_publish,
            'postedAt'      => $this->updated_at,
            'userName'      => $name,
            'userAvatar'    => $userAvatar,
            'isMe'          => $isMe,
            'replied_by'     => $this->role_id ? RoleType::getKey($this->role_id) : "",
            'is_superAdmin' => $this->role_id and $this->role_id == RoleType::SuperAdmin ? true : false,
            'isReplied' =>    count($parent_comment) > 0 ? true : false,
            
        ];

        $data['spamCount'] = $this->spams ? count($this->spams) : 0;
        $authSpam = false;
        if (count($this->spams) && $user) {
            $isCount = $this->spams->where('created_by', $user->id)->first(); 
            $authSpam = $isCount ? true : false;
        }

        $data['authSpam'] = $authSpam;
        return $data;
    }
}
