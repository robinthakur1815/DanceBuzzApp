<?php

namespace App;

use App\Model\Partner\PartnerMedia;
use DateTimeInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use League\OAuth2\Server\Exception\OAuthServerException;

use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $connection = 'mysql2';
    // protected $table = 'mysql2.dbo.users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'username', 'phone', 'email_verified_at', 'password',
        'role_id', 'is_active', 'remember_token', 'app_type',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function avatar()
    {
        return $this->setConnection('mysql')->morphToMany(\App\Media::class, 'mediable');
    }

    public function getPartnerAvatarAttribute()
    {
        $url = '';
        $avatar = PartnerMedia::where('model_id', $this->id)->where('model_type', config('app.user_model_type'))->first();
        if ($avatar) {
            $url = config('app.s3url').'/'.$avatar->url;
        }

        return $url;

        // return $this->setConnection('mysql')->morphToMany('App\Media', 'mediable');
    }

    public function avatarMediable()
    {
        return $this->setConnection('mysql')->morphOne(\App\Mediables::class, 'mediable');
    }

    public function profile()
    {
        return $this->setConnection('mysql')->hasOne(\App\UserProfile::class);
    }

    public function tokens()
    {
        return $this->hasMany(\App\DeviceToken::class, 'user_id');
    }

    public function AauthAcessToken(){
        return $this->hasMany('\App\OauthAccessToken');
    }
    
    
   

    public function student()
    {
        return $this->hasOne(\App\Student::class, 'user_id');
    }

    public function Guardian()
    {
        return $this->hasOne(\App\Guardian::class, 'user_id');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // checking if user is active or not for issue token
    // public function findForPassport($username)
    // {
    //     $user = $this->where('email', $username)->first();
    //     if ($user !== null && $user->is_active == 0) {
    //         throw new OAuthServerException('User is invalid or the account has been disabled.', 6, 'account_inactive', 401);
    //     }
    //     return $user;
    // }
}
