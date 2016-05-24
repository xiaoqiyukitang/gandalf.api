<?php
/*
 * This code was generated automatically by Nebo15/REST
 */
namespace App\Models;

use App\Exceptions\TokenExpiredException;
use App\Exceptions\TokenNotFoundException;
use App\Services\Hasher;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Nebo15\LumenApplicationable\Contracts\ApplicationableUser as ApplicationableUserContract;
use Nebo15\LumenApplicationable\Traits\ApplicationableUserTrait;
use Nebo15\LumenOauth2\Interfaces\Oauthable as OauthableContract;
use Nebo15\LumenOauth2\Traits\Oauthable;
use Nebo15\REST\Traits\ListableTrait;
use Nebo15\REST\Interfaces\ListableInterface;

class User extends Base implements
    ListableInterface,
    AuthenticatableContract,
    AuthorizableContract,
    OauthableContract,
    ApplicationableUserContract
{
    use ListableTrait, Authenticatable, Authorizable, Oauthable, ApplicationableUserTrait;

    protected $listable = [
        '_id',
        'username',
        'first_name',
        'last_name',
    ];

    protected $visible = ['_id', 'username', 'temporary_email', 'email', 'first_name', 'last_name', 'active', 'tokens'];

    protected $fillable = ['username', 'temporary_email', 'email', 'active', 'password', 'first_name', 'last_name'];

    protected $attributes = [
        'active' => false,
        'email' => '',
        'temporary_email' => '',
        'tokens' => [],
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function createVerifyEmailToken()
    {
        $this->attributes['tokens']['verify_email'] = [
            'token' => Hasher::getToken(),
            'expired' => time() + 3600,
        ];

        return $this;
    }

    public function getVerifyEmailToken()
    {
        return $this->getInternalToken('verify_email');
    }

    public function removeVerifyEmailToken()
    {
        unset($this->attributes['tokens']['verify_email']);

        return $this;
    }

    public function verifyEmail()
    {
        $this->email = $this->temporary_email;
        $this->temporary_email = null;
        $this->active = true;
        $this->removeVerifyEmailToken();

        return $this;
    }

    public function findByVerifyEmailToken($token)
    {
        return $this->findByToken($token, 'verify_email');
    }

    public function findByToken($token, $type, $field = null, $value = null)
    {
        $query = $this->where("tokens.$type.token", '=', $token);
        if ($field and $value) {
            $query->where($field, '=', $value);
        }
        if (!$user = $query->first()) {
            throw new TokenNotFoundException;
        }
        if ($user->tokens[$type]['expired'] <= time()) {
            throw new TokenExpiredException;
        }

        return $user;
    }

    private function getInternalToken($type)
    {
        return (array_key_exists($type, $this->attributes['tokens'])) ?
            $this->attributes['tokens'][$type] :
            false;
    }
}
