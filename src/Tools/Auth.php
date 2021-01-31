<?php

namespace Cube\Tools;

use ReflectionClass;
use InvalidArgumentException;

use Cube\App;
use Cube\Modules\DB;

use Cube\Http\Session;
use Cube\Http\Cookie;

use Cube\Misc\EventManager;
use Cube\Exceptions\AuthException;
use Cube\Interfaces\ModelInterface;

class Auth
{

    const EVENT_ON_AUTHENTICATED = 'authenticated';
    const EVENT_ON_LOGGED_OUT    = 'loggedout';

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
            throw new AuthException('Account not found for ' . $field .  ' using ' . $auth_field_name);
        }

        $raw_server_secret = $query->{$secret_key_name};
        $secret_is_valid = $hash_method($secret, $raw_server_secret);

        if(!$secret_is_valid) {
            throw new AuthException('Invalid account credentials');
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
     * @param string $model Model to retrieve data
     * @return bool
     */
    public static function byField($field, $value, $model)
    {
        $config = static::getConfig();
        $primary_key = $config['primary_key'] ?? null;
        $instance = $config['instance'] ?? null;

        if(!$primary_key) {
            throw new InvalidArgumentException('Auth config "Primary key" not assigned');
        }

        $model_class = new ReflectionClass($model);

        if(!$model_class->implementsInterface(ModelInterface::class)) {
            throw new InvalidArgumentException('Auth config instance not specified');
        }

        $data = $model::findBy($field, $value);

        if(!$data) {
            throw new AuthException('Authentication data not found');
        }

        $schema_primary_key = $data->{$primary_key};

        #Dispatch logged in event
        EventManager::dispatchEvent(self::EVENT_ON_AUTHENTICATED, $schema_primary_key);

        Session::set(static::$_auth_name, $schema_primary_key);
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

        Session::remove(static::$_auth_name);
        Cookie::remove(static::$_auth_name);

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
        $instance = static::getConfig('model');

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
            return false;
        }

        static::$_auth_user = $instance::find($auth_id);
        #update cookie
        static::setUserCookieToken($user_id);
        return static::$_auth_user;
    }

    /**
     * Get Auth config
     * 
     * @param string|null $field
     * 
     * @return array|object|null
     */
    private static function getConfig($field = null)
    {
        if(!static::$_config) {
            static::$_config = App::getConfigByName('auth');
        }

        if(!static::$_config) {
            throw new AuthException('Auth config not found in "' . CONFIG_PATH . '"');
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
        $cookie_table = DB::table(static::$_cookie_token_dbname);
        $token = generate_token(32);

        $cookie_table->replace([
            'user_id' => $user_id,
            'token' => $token,
            'expires' => gettime(time() + (30 * 24 * 60 * 60))
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
        $cookie_table = DB::table(static::$_cookie_token_dbname);

        #Check if cookie table exists
        #If not create the table with it's fields
        if(!DB::hasTable(static::$_cookie_token_dbname)) {
            $cookie_table
                ->create(function ($table) {
                    $table->field('user_id')->varchar()->primary();
                    $table->field('token')->text();
                    $table->field('expires')->datetime();
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

        #Check if cookie table exists
        #If not create the table with it's fields
        if(!DB::hasTable(static::$_cookie_token_dbname)) {
            $cookie_table
                ->create(function ($table) {
                    $table->field('user_id')->varchar()->primary();
                    $table->field('token')->text();
                    $table->field('expires')->datetime();
                }); 
        }

        $schema_primary_key = static::getConfig('primary_key');
        $is_valid = $cookie_table
                        ->select(['user_id', 'token'])
                        ->where('token', $token)
                        ->fetchOne();

        if(!$is_valid) {
            return false;
        }

        #Update user's token to a new hash
        $new_token = static::setUserCookieToken($is_valid->user_id);

        #Set user
        return $is_valid->user_id;
    }
}