<?php

namespace ApiFramework;

class Auth extends BaseModule
{

    /**
     * @var string Users table
     */
    protected $table = 'users';

    /**
     * @var array Filters for where, order, etc.
     */
    protected $validFilters = ['id' => 'id', 'email' => 'users.email'];

    /**
     * @var Array Authenticated user
     */
    private $user = null;


    /**
     * Login a user
     *
     * @param string $key
     * @return mixed
     */
    public function attempt ($username, $password, $remember = false) {

        // Check credentials
        if (!$this->validate($username, $password)) {
            return $this->error(401, 'Invalid user');
        }

        // Store session file
        $token = $this->createTokenHash($username);

        // Get user data
        $user = $this->findUser($username);
        unset($user[$this->app->config('auth.password')]);
        $this->user = $user;

        // Save session
        $sessionSaved = $this->storeSession($token, $user);

        // Set cookie
        if ($remember) {
            $cookieSet = $this->setCookie($token);
        }

        // Return username and token
        return ['user' => $user, 'token' => $token];
    }


    /**
     * Returns the info of the logged user
     *
     * @param string $token Token
     * @return array User session data
     */
    public function info ($token) {

        // Check session
        $sessionData = $this->getSession($token);
        if (!$sessionData) {
            return $this->error(401, 'Invalid token');
        }

        // Return user info
        return $sessionData;
    }


    /**
     * Logouts a user
     *
     * @param string $token Token
     * @return boolean Success or fail of logout
     */
    public function logout ($token) {

        // Get session
        $sessionData = $this->getSession($token);

        // Check session
        if (!$sessionData) {
            return $this->error(401, 'Invalid token');
        }

        // Delete session cookies
        $cookieDeleted = $this->deleteCookie();
        $sessionDeleted = $this->deleteSession($token);

        // Return success or fail
        return $cookieDeleted && $sessionDeleted;
    }


    /**
     * Validates a user - password pair
     *
     * @param string $username Username to validate
     * @param string $password Raw password
     * @return boolean Success or fail of validation
     */
    public function validate ($username, $password) {

        // Get the user's data
        $user = $this->findUser($username);

        // Abort if the user does not exist
        if (!$user) {
            return false;
        }

        // Verify the password against the stored hash
        return password_verify($password, $user[$this->app->config('auth.password')]);
    }


    /**
     * Creates a reminder and sends it to the user
     *
     * @param string $username Username to validate
     * @return boolean Success or fail of reminder saving and sending
     */
    public function remindPassword ($username) {

        // Get the user's data
        $user = $this->findUser($username);

        // Abort if the user does not exist
        if (!$user) {
            return false;
        }

        // Creates the reminder
        $reminder = $this->createReminderHash($username);

        // Saves the reminder
        $reminderSaved = $this->storeReminder($reminder, [$this->app->config('auth.username') => $username]);

        // Return
        return $reminderSaved;
    }


    /**
     * Changes the password of a user
     *
     * @param string $token Token or temporal token
     * @return boolean Success or fail of password change
     */
    public function changePassword ($username, $token, $newPassword) {

        // Get data from reminder or session
        $isReminder = $this->isReminder($token);
        $sessionData = $isReminder ? $this->getReminder($token) : $this->getSession($token);

        // Abort if the session is invalid
        if (!$sessionData) {
            $type = $isReminder ? 'reminder' : 'token';
            return $this->error(401, 'Invalid ' . $type);
        }

        // Abort if the reminder is expired
        $maxage = time() + $this->app->config('reminders.ttl');
        if ($isReminder && (!isset($sessionData['expires']) || $sessionData['expires'] > $maxage)) {
            return $this->error(401, 'Invalid reminder code');
        }

        // Get user
        $user = $this->findUser($username);

        // Hash password
        $data['password'] = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update user
        $userUpdated = $this->update($id, $data);
        /*
        $db = new PDO('mysql:host=localhost;dbname=project', 'root', 'root');
        $statement = $db->prepare('UPDATE users SET password = :password where id = :id limit 1');
        $userUpdated = $statement->execute(array(':id' => $user['id'], ':password' => $data['password']));
        */

        // Delete reminder
        if ($userUpdated['sucess'] && $isReminder) {
            $reminderDeleted = $this->deleteReminder($token);
        }

        // Return status
        return $userUpdated;
    }


    /**
     * Gets a user from the database
     *
     * @param string $username Username
     * @return mixed User array, of false if the user does not exist
     */
    private function findUser ($username) {
        $user = $this->where($this->app->config('auth.username'), $username)->first();
        /*
        $db = new PDO('mysql:host=localhost;dbname=project', 'root', 'root');
        $statement = $db->prepare('SELECT * FROM users WHERE email = :username');
        $statement->execute(array(':username' => $username));
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        */
        return $user['data'];
    }


    /**
     * Creates a random token hash based on the username and time
     *
     * @param string $username Username to hash
     * @return string Token
     */
    private function createTokenHash ($username) {
        return md5($username . time() . rand(0, 100));
    }


    /**
     * Returns the session path for a given token
     *
     * @param string $token Token
     * @return string Session path
     */
    private function sessionPath ($token) {
        return $this->app->config('sessions.folder') . $token . '.json';
    }


    /**
     * Stores the session data on a file
     *
     * @param string $token Token
     * @param string $data Data to store
     * @return boolean Success or fail of file writing
     */
    private function storeSession ($token, $data) {
        $path = $this->sessionPath($token);
        return file_put_contents($path, json_encode([
            'user' => $data,
            'token' => $token,
            'ctime' => time()
        ]));
    }


    /**
     * Retrieves data from the session file
     *
     * @param string $token Token
     * @return mixed Session data, or false if the session is invalid
     */
    private function getSession ($token) {
        $path = $this->sessionPath($token);
        return (file_exists($path)) ? json_decode(file_get_contents($path), true) : false;
    }


    /**
     * Deletes the session file
     *
     * @param string $token Token
     * @return boolean Success or fail of file delete
     */
    private function deleteSession ($token) {
        $path = $this->sessionPath($token);
        return unlink($path);
    }


    /**
     * Creates a random reminder hash on the username and time
     *
     * @param string $username Username to hash
     * @return string Reminder
     */
    private function createReminderHash ($username) {
        return $this->app->config('reminders.suffix') . md5($username . time() . rand(0, 100));
    }


    /**
     * Returns the reminder path for a given reminder
     *
     * @param string $reminder Reminder
     * @return string Reminder path
     */
    private function reminderPath ($reminder) {
        return $this->app->config('reminders.folder') . $reminder . '.json';
    }


    /**
     * Stores the reminder data on a file
     *
     * @param string $reminder Reminder
     * @param string $data Data to store
     * @return boolean Success or fail of file writing
     */
    private function storeReminder ($reminder, $data) {
        $path = $this->reminderPath($reminder);
        return file_put_contents($path, json_encode([
            'user' => $data,
            'reminder' => $reminder,
            'expires' => time() + $this->app->config('reminders.ttl')
        ]));
    }


    /**
     * Retrieves the data from the reminder file
     *
     * @param string $reminder Reminder
     * @return mixed Reminder data, or false if the reminder is invalid
     */
    private function getReminder ($reminder) {
        $path = $this->reminderPath($reminder);
        return (file_exists($path)) ? json_decode(file_get_contents($path), true) : false;
    }


    /**
     * Deletes the reminder file
     *
     * @param string $reminder Reminder
     * @return boolean Success or fail of file delete
     */
    private function deleteReminder ($reminder) {
        $path = $this->reminderPath($reminder);
        return unlink($path);
    }


    /**
     * Check if a hash is a reminder
     *
     * @param string $string String to evaluate
     * @return boolean True if the string is a reminder
     */
    private function isReminder ($string) {
        return strpos($string, $this->app->config('reminders.suffix')) === 0;
    }


    /**
     * Sets the token cookie
     *
     * @param string $token Token
     * @return boolean Success or fail of cookie writing
     */
    private function setCookie ($token) {
        $ttl = $this->app->config('sessions.ttl');
        $cookie = $this->app->config('sessions.cookie');
        return setcookie($cookie, $token, time() + $ttl, '/');
    }


    /**
     * Deletes the token cookie
     *
     * @return boolean Success or fail of cookie writing
     */
    private function deleteCookie () {
        $cookie = $this->app->config('sessions.cookie');
        return setcookie($cookie, '', time() - 3600, '/');
    }

}