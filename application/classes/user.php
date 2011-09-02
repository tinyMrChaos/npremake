<?php

class user{
    private $user_data = array();
    private static $instance;
    protected $user_id;
    
	/**
	 * Constructor
	 *
	 * @param int $user_id       - Specific user id. Pass FALSE to use username and password
	 * @param str $username      - Ignored if $user_id is passed
	 * @param str $password      - Plain text password, ignored if $user_id is passed
	 * @param str $instance_name - Instance name
	 * @param bol $session       - Defines if the logged in user id should be saved in session
	 */
	public function __construct($username = false, $password = false)
	{
		if (($username) && ($password̈́))
		{
			$instance->login_by_username_and_password($username, $password);
		}
	}
	
    /**
	 * Instance
	 * Singleton function
	 *
	 * @return object
	 */
    static function instance(){
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
        
    }
    
    /**
	 * getId
	 * returns the current users userID
	 *
	 * @return int
	 */
    function getId(){
        return $this->user_id;
    }
    
    /**
	 * encrypt_password
	 * encrypts and returns the string
	 *
	 * @param string password
	 * @return string
	 */
    public static function encrypt_password($password){
        return md5($password);
    }
    
    /**
	 * Create User
	 * Creates a basic user to access more of the site
	 *
	 * @param string fname
	 * @param string lname
	 * @param string username
	 * @param string password
	 * @return int
	 */
    public static function create_user($fname, $lname, $username, $password){
        return DB::insert('user', array('fname', 'lname', 'username', 'password', 'accesskey', 'acceptTos'))
                            ->values(
                                array(
                                    'fname' => $fname,
                                    'lname' => $lname,
                                    'username' => $username,
                                    'password' => self::encrypt_password($password),
                                    'accesskey' => md5(rand()),
                                    'acceptTos' => 1
                                )
                             )
                            ->execute();
    }
    
    /**
	 * Login by username and password
	 * Takes username and password, searches the db for a match and then calls login_by_user_id
	 * 
	 * @param string username
	 * @param string password
	 * @return mixed
	 */
    function login_by_username_and_password($username, $password){
        $id = DB::select('user_id')
                ->from('user')
                ->where('username','=',$username)
                ->and_where('password','=',md5($password))
                ->execute()
                ->as_array();
        if(count($id) > 0){
            return $this->login_by_user_id($id[0]['user_id']);
        } else {
            return false;
        }
    }
    
    /**
	 * Login by user id
	 * Takes userid and logs the user in
	 *
	 * @param int id
	 * @return mixed
	 */
    function login_by_user_id($id){
        if($this->instance()->get_username_by_id($id)){
            return $this->instance()->load_user_data($id);
        } else {
            return false;
        }
    }
    
    /**
	 * Load user data
	 * Called when login occurs. Loads the users data into the object
	 *
	 * @param int id
	 * @return mixed
	 */
    function load_user_data($id){
        $data = $this->get_user_data($id);
        if($data){
            $this->instance()->user_id = $id;
            $this->instance()->user_data = $data;
            return $this;
        }
        return false;
    }
    
    /**
	 * Get user data
	 * Gets the userdata from the database
	 *
	 * @param int id
	 * @return array
	 */
    public static function get_user_data($id){
        $data = DB::select('*')->from('user')->where('user_id','=',$id)->execute()->as_array();
        if(count($data)){
            unset($data[0]['user_id'], $data[0]['password']);
            return $data[0];
        } else {
            return array();
        }
    }
    
    /**
	 * getUserGroups
	 * Gets the groups a user is a member of along with membertype and year.
	 * 
	 * @param int user_id
	 * @return array
	 */
    public static function get_user_groups($user_id){
        $groups = DB::select('ltug.year', 'ltug.groupid', array('g.name','groupname'), array('mt.name','membertype'))
                    ->from(array('lt_UserGroup', 'ltug'))
                    ->join(array('group', 'g'))
                    ->on('g.id','=','ltug.groupid')
                    ->join(array('membertype', 'mt'))
                    ->on('ltug.type','=','mt.id')
                    ->where('ltug.userid','=',$user_id)
                    ->execute()->as_array();
        if(count($groups)){
            return $groups;
        } else {
            return array();
        }
    }
    
    /**
	 * Get Group
	 * Gets a single group if group_id is presented, if not, returns all groups
	 *
	 * @param group_id
	 * @return array
	 */
    public static function get_group($group_id = false){
        $data = DB::select('*')
                    ->from(array('group', 'g'))
                    ->order_by('name', 'asc');
        if($group_id !== false){
            $data = $data->where('id', '=', $group_id);
        }
        return $data->execute()->as_array();
    }
    
    /**
	 * getMembertype
	 * Returns single membertype if presented with type_id, if not, returns all membertypes
	 *
	 * @param type_id
	 * @return array
	 */
    public static function get_membertype($type_id = false){
        $data = DB::select('*')
                    ->from(array('membertype', 'mt'))
                    ->order_by('name', 'asc');
        if($type_id !== false){
            $data = $data->where('id', '=', $type_id);
        }
        return $data->execute()->as_array();
    }
    
    /**
	 * getProgram
	 * Returns single program if argument present. if not, returns all programs
	 *
	 * @param program_id
	 * @param sort
	 * @return array
	 */
    public static function get_programs($program_id = false, $sort = false){
        $data = DB::select('*')
                        ->from('program');
        if($program_id !== false)
            $data = $data->where('id', '=', $program_id);
        if($sort)
            $data = $data->order_by('name', 'asc');
            
        $data = $data->execute()->as_array();
        $return = array();
        foreach($data as $d)
            $return[$d['id']] = $d['name'];
        return $return;
    }
    
    /**
	 * Add user to group
	 * adds users to a group using membertype. 
	 *
	 * @param mixed user_id can be either single user_id or array of users to be added to group 
	 * @param int group_id
	 * @param int membertype
	 * @return object - Returns the current instance
	 */
    public static function add_user_to_group($user_id, $group_id, $membertype){
        if(!is_array($user_id)) //Just in case someone misses it.
            $user_id = array($user_id);
            
        $query = DB::insert('lt_UserGroup', array('userid', 'groupid', 'type', 'year'));
        foreach($user_id as $uid){
            $query->values(array('userid' => $uid, 'groupid' => $group_id, 'type' => $membertype, 'year' => date('Y')));
        }
        try{
             $query->execute();
             $_SESSION['messages']['success'][] = "The users have been added to the groups";
        } catch(Exception $e){
            $_SESSION['messages']['fail'][] = "One or more of the users already existed in that group with that role";
        }
    }
    
    /**
	 * Remove one or more users from one or more groups
	 *
	 * @param mixed int or array of userIDs
	 * @param mixed int or array of groupIDs
	 */
    public static function removeUserFromGroup($user_id, $group_id){
        if(!is_array($user_id))
            $user_id = array($user_id);
        if(!is_array($group_id))
            $group_id = array($group_id);
        
        $query = DB::delete('lt_UserGroup')
                    ->where('groupid', 'in', DB::Expr('('.implode(',', $group_id).')'))
                    ->and_where('userid','in',DB::Expr('('.implode(',',$user_id).')'))
                    ->execute();
    }
    
    /**
	 * add group
	 * Add a group to the database
	 *
	 * @param string groupname
	 * @return int 
	 */
    public static function add_group($groupname){
        return DB::insert('group', array('name'))->values(array('name' => $groupname))->execute();
    }
    /**
	 * deleter group
	 * Add a group to the database
	 *
	 * @param int groupid
	 * @return int 
	 */
    public static function del_group($groupid){
        DB::delete('lt_UserGroup')
                ->where('groupid', '=', $groupid)
                ->execute();
        DB::delete('group')
                ->where('id', '=', $groupid)
                ->execute();
    }
    
    /**
	 * Get username by id
	 * returns the username corresponding to the user_id
	 *
	 * @param int id
	 * @return mixed string username if success else false
	 */
    static function get_username_by_id($id){
        $username = DB::select('username')->from('user')->where('user_id','=',$id)->execute()->as_array();
        if(count($username) > 0){
            return $username[0]['username'];
        } else {
            return false;
        }
    }
    
    /**
	 * Logged in
	 * Checks to see if the user is currently logged in.
	 *
	 * @return bool
	 */
    function logged_in(){
//        var_dump(isset($this->user_id), is_numeric($this->user_id));
        if(isset($this->user_id) && is_numeric($this->user_id)){
            return true;
        } else {
            return false;
        }
    }
    
    /**
	 * isAdmin
	 * Checks whether or not the user has administrative rights
	 *
	 * @return bool
	 */
    function isAdmin(){
        if($this->logged_in()) {
            //Does he have the membertype of NPG for this year?
	        $data = DB::select(DB::expr('count(1)'))
	            ->from('lt_UserGroup')
	            ->where('userid', '=', $this->getId())
	            ->where('type', '=', 1)
	            ->where('year', '=', DB::expr('year(now())'))
	            ->execute()
	            ->as_array();
	        if($data[0]['count(1)'] > 0){
	            //Guess he is. Let's return true and end it right here.
	            return true;
	        }
	        //If not. Lets Check if he belongs with the mighty Bullfika
	        $data = DB::select(DB::expr('count(1)'))
	            ->from('lt_UserGroup')
	            ->where('userid', '=', $this->getId())
	            ->where('groupid', '=', 2)
	            ->execute()
	            ->as_array();
	        if($data[0]['count(1)'] > 0){
	            //Guess he is. Let's return true and end it right here.
	            return true;
	        }
        } else 
            return false;
    }
    
    /**
	 * get full name
	 * Returns the full name of the current user
	 *
	 * @return string
	 */
    public function get_full_name(){
        return $this->user_data['fname'].' '.$this->user_data['lname'];
    }
    
    /**
	 * free username
	 * Used in the validation process to check whether or not a username is taken
	 *
	 * @param string username
	 * @return bool
	 */
    static function free_username($username){
        $free = DB::select('username')->from('user')->where('username','=',$username)->execute()->as_array();
        if(count($free) == 0)
            return true;
        else 
            return false;
    }
    
    /**
	 * change_user_details
	 * Updates user details according to details
	 * 
	 * @param int id the user id
	 * @param array details key-value pairs of the new details
	 */
    static function change_user_details($id, $details){
        unset($details['user_id']);
        $q = DB::update('user')
	            ->set($details)
	            ->where('user_id','=', $id)
	            ->execute();
    }

}




?>