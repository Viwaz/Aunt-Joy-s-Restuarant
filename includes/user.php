<?php
require_once 'database.php';
/**
 * User class handles all user-related operations
 * it interacts with the 'users' table in the database
 * it provides methods for creating, reading, updating, and deleting users by the admin/ users as well
 */

class User{
    // database connection and table name
    private $conn;
    private $table = "users";

    public function __construct($db){
        $this->conn = $db;
    }


    public function create($user, $email,$password, $role = 'customer'){
    /**Function for creating users
     * parameters:
     *  - username, - email, - password
     * these are captured when the user clicks 'register'
     * */
        $query = "INSERT INTO $this->table(username, email, password, role)
                  VALUES (?,?,?,?)";
        $insert  = $this->conn->prepare($query);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // assigning(binding) the values to the corresponding placeholders in the query
        $insert->bind_param("ssss", $user, $email, $password_hash, $role);
        return $insert->execute();
    }

    public function readAll(){
        /**FUNCTION FOR VIEWING USERS
         *- reads all users present in the database
         *
         */
        $readAll = $this->conn->prepare("SELECT * FROM $this->table ORDER BY id DESC");
        $readAll->execute();
        $result = $readAll->get_result();
        return $result ;
    }

    public function update($username,$email,$role,$id){
        /**FUNCTION FOR UPDATING USER DETAILS
         * -allows for update of values excluding the password and Id
         */
        $query = "UPDATE $this->table SET username = ?, email = ?, role = ? WHERE id = ?";
        $update = $this->conn->prepare($query);
        $update ->bind_param('sssi', $username,$email,$role,$id);
        return $update->execute;

    }

    public function delete($id) {
        $query = "DELETE FROM $this->table WHERE id=?";
        $delete = $this->conn->prepare($query);
        $delete->bind_param("i", $id);
        return $delete->execute();
    }
}



?>