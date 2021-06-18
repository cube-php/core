<?php

namespace Cube\Tools;

use ReflectionClass;
use InvalidArgumentException;

use Cube\App\App;
use Cube\App\Directory;
use Cube\Modules\DB;

use Cube\Http\Session;
use Cube\Http\Cookie;

use Cube\Misc\EventManager;
use Cube\Exceptions\AuthException;
use Cube\Interfaces\ModelInterface;
use Cube\Modules\Db\DBTable;

class Auth
{
    /**
     * Error message when authentication fails
     */
    public const CONFIG_ERROR_MSG = 'error_msg';
    
    /**
     * Table name for users
     */
    public const CONFIG_SCHEMA = 'schema';

    /**
     * Password hash method
     */
    public const CONFIG_HASH_METHOD = 'hash_method';

    /**
     * Authenticaton attempt combination
     */
    public const CONFIG_COMBINATION = 'combination';

    /**
     * Table primary key Id
     */
    public const CONFIG_PRIMARY_KEY = 'primary_key';

    /**
     * Users model class
     */
    public const CONFIG_MODEL = 'instance';

    /**
     * No. of days it will take an auth token to expire
     */
    public const CONFIG_COOKIE_EXPIRY_DAYS = 'cookie_expiry_days';

    public const EVENT_ON_AUTHENTICATED = 'authenticated';
    public const EVENT_ON_LOGGED_OUT    = 'loggedout';

    /**
     * Authentication configuration
     * 
     * @var string[]
     */
    private static $_config;

    /**
     * Get authentication status
     * 
     * @var string[]
     */
    private static $_auth_name = 'session_auth';

    /**
     * Auth cookie device name
     *
     * @var string
     */
    private static $_device_name = 'session_auth_device_id';

    /**
     * Cube cookie token dbname
     *
     * @var string
     */
    private static $_cookie_token_dbname = 'cube_auth_tokens';

    /**
     * Get authenticated user
     * 
     * @var object
     */
    private static $_auth_user;

    /**
     * Attempt authentication
     * 
     * @param array $combination
     * @param boolean $remember
     * 
     * @return object|boolean
     */
    public static function attempt($field, $secret, $remember = false)
    {
        #Load the auth configuaration
        $config = static::getConfig();
        $error_msg = $config[self::CONFIG_ERROR_MSG] ?? 'Invalid Account Credentials';

        $hash_method = $config['hash_method'] ?? 'password_verify';
        $primary_key = $config['primary_key'] ?? null;
        $schema = $config['schema'] ?? null;
        $config_combination = (array) $config['combination'];

        if(!$schema) {
            throw new AuthException('Auth schema field is undefined');
        }

        $auth_fields = $config_combination['fields'] ?? null;

        if(!$auth_fields) {
            throw new AuthException('Authentication fields not specified');
        }

        $auth_fields_names = array_keys($auth_fields);

        if(!$field) {
            throw new AuthException(
                concat('Enter ', implode('or ', $auth_fields_names), ' to login')
            );
        }

        $auth_field_name = null;
        $default_field_name = null;

        foreach($auth_fields as $field_name => $fn) {
            if($fn && $fn($field)) {
                $auth_field_name = $field_name;
                break;
            }

            if(!$fn) {
                $default_field_name = $field_name;
                continue;
            }
        }

        $auth_field_name = $auth_field_name ?: $default_field_name;
        $secret_key_name = $config_combination['secret_key'];

        $query = DB::table($schema)
                    ->select([$primary_key, $secret_key_name])
                    ->where($auth_field_name, $field)
                    ->fetchOne();

        if(!$query) {
            throw new AuthException($error_msg);
        }

        $raw_server_secret = $query->{$secret_key_name};
        $secret_is_valid = $hash_method($secret, $raw_server_secret);

        if(!$secret_is_valid) {
            throw new AuthException($error_msg);
        }

        $schema_primary_key = $query->{$primary_key};

        #Check for the remeber feature
        if($remember) {
            static::setUserCookieToken($schema_primary_key);
        }

        #Dispatch logged in event
        EventManager::dispatchEvent(self::EVENT_ON_AUTHENTICATED, $schema_primary_key);

        Session::set(static::$_auth_name, $schema_primary_key);
        return true;
    }

    /**
     * Authenticate user by using field values
     *
     * @param string $field Field name
     * @param string $value Field value
     * @param bool $remember Keep user logged in
     * @return bool
     */
    public static function byField($field, $value, bool $remember = false)
    {
        static::logout();

        $config = static::getConfig();
        $primary_key = $config[self::CONFIG_PRIMARY_KEY] ?? null;
        $instance = $config[self::CONFIG_MODEL] ?? null;

        if(!$primary_key) {
            throw new InvalidArgumentException('Auth config "Primary key" not assigned');
        }

        $data = $instance::findBy($field, $value);

        if(!$data) {
            throw new AuthException('Authentication data not found');
        }

        $schema_primary_key = $data->{$primary_key};

        #Dispatch logged in event
        EventManager::dispatchEvent(
            self::EVENT_ON_AUTHENTICATED,
            $schema_primary_key
        );
        
        Session::set(static::$_auth_name, $schema_primary_key);
        
        if($remember) {
            Cookie::set(static::$_auth_name, $schema_primary_key);
        }

        return true;
    }

    /**
     * End current authenticated user's session
     *
     * @return bool
     */
    public static function logout()
    {
        $id = Session::get(static::$_auth_name);
        $token = Cookie::get(static::$_auth_name);
        $device_id = static::getDeviceId();

        Session::remove(static::$_auth_name);
        Cookie::remove(static::$_auth_name);

        static::getDbTable()->delete()
            ->where('user_id', $id)
            ->and('token', $token)
            ->and('device_id', $device_id)
            ->fulfil();

        #Dispatch loggedout event
        EventManager::dispatchEvent(self::EVENT_ON_LOGGED_OUT, $id);
        return true;
    }

    /**
     * Get the current authenticated user
     * 
     * @return object|boolean
     */
    public static function user()
    {
        if(static::$_auth_user) {
            return static::$_auth_user;
        }

        #Check for authenticated session
        $auth_id = Session::get(static::$_auth_name);
        $instance = static::getConfig(self::CONFIG_MODEL);

        if(!$instance) {
            throw new AuthException('Model not specified');
        }

        $instance_reflector = new ReflectionClass($instance);
        $model_inteface = ModelInterface::class;

        if(!$instance_reflector->implementsInterface($model_inteface)) {
            throw new AuthException($instance . ' does not implement ' . $model_inteface);
        }

        if($auth_id) {
            static::$_auth_user = $instance::find($auth_id);
            return static::$_auth_user;
        }

        #Check for user auto log cookie
        $cookie_token = Cookie::get(static::$_auth_name);

        if(!$cookie_token) {
            return null;
        }

        $user_id = static::validateAuthCookieToken($cookie_token);

        if(!$user_id) {
            static::logout();
            return null;
        }

        static::$_auth_user = $instance::find($user_id);
        Session::set(static::$_auth_name, $user_id);

        return static::$_auth_user;
    }

    /**
     * Get Auth config
     * 
     * @param string|null $field
     * 
     * @return array|object|string|null
     */
    private static function getConfig($field = null)
    {
        if(!static::$_config) {
            static::$_config = App::getConfig('auth');
        }

        $config_path = App::getPath(Directory::PATH_CONFIG);

        if(!static::$_config) {
            throw new AuthException('Auth config not found in "' . $config_path . '"');
        }

        if($field) {
            return static::$_config[$field] ?? null;
        }

        return static::$_config;
    }

    /**
     * Create new user cookie token
     *
     * @return string Generated user token
     */
    private static function setUserCookieToken($user_id)
    {
        $days = static::getConfig(
            static::CONFIG_COOKIE_EXPIRY_DAYS
        );

        $token = generate_token(32);
        $expires_at = gettime(time() + getdays($days));

        static::getDbTable()->insert([
            'user_id' => $user_id,
            'device_id' => static::getDeviceId(),
            'token' => $token,
            'expires_at' => $expires_at
        ]);

        Cookie::set(static::$_auth_name, $token);
        return $token;
    }

    /**
     * Create schema
     *
     * @return boolean
     */
    public static function up()
    {   
        #Check if cookie table exists
        #If not create the table with it's fields
        if(!DB::hasTable(static::$_cookie_token_dbname)) {
            static::getDbTable()
                ->create(function ($table) {
                    $table->field('id')->int()->increment();
                    $table->field('user_id')->varchar();
                    $table->field('device_id')->varchar();
                    $table->field('token')->text();
                    $table->field('expires_at')->datetime();
                }); 
        }
        
        return true;
    }

    /**
     * Validate user's cookie
     *
     * @param string $token
     * @return boolean
     */
    private static function validateAuthCookieToken($token)
    {
        $cookie_table = DB::table(static::$_cookie_token_dbname);
        $is_valid = $cookie_table
                        ->select(['user_id', 'token'])
                        ->where('token', $token)
                        ->fetchOne();

        if(!$is_valid) {
            return false;
        }

        #Set user
        return $is_valid->user_id;
    }

    /**
     * Get auth tokens table
     *
     * @return DBTable
     */
    private static function getDbTable(): DBTable
    {
        return DB::table(static::$_cookie_token_dbname);
    }

    /**
     * Get auth device id
     *
     * @return string
     */
    private static function getDeviceId(): ?string
    {
        return Cookie::getOrSet(static::$_device_name, function () {
            return generate_token(20);
        });
    }
}