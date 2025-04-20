<?php

require_once 'db_connect.php';

/**
 * Function to log user login activities to SystemLogs table
 * 
 * @param int $userID 
 * @param string $userType 
 * @param string $email 
 * @return bool 
 */
function logUserLogin($userID, $userType, $email) {
    global $conn;
    
    
    $adminID = ($userType === 'Admin') ? $userID : 'NULL';
    
    
    $actionDesc = "User login: $userType ($email) logged in successfully";
    
    
    $query = "INSERT INTO SystemLogs (AdminID, ActionDesc) VALUES ($adminID, '$actionDesc')";
    
    
    $result = mysqli_query($conn, $query);
    
    
    return ($result) ? true : false;
}

/**
 * Function to log failed login attempts
 * 
 * @param string $email 
 * @param string $reason 
 * @return bool 
 */
function logFailedLogin($email, $reason) {
    global $conn;
    
    
    $actionDesc = "Failed login attempt: $email - Reason: $reason";
    
    
    $query = "INSERT INTO SystemLogs (AdminID, ActionDesc) VALUES (NULL, '$actionDesc')";
    
    
    $result = mysqli_query($conn, $query);
    
    
    return ($result) ? true : false;
}

/**
 * Function to log user logout activities
 * 
 * @param int $userID 
 * @param string $userType 
 * @param string $email 
 * @return bool 
 */
function logUserLogout($userID, $userType, $email) {
    global $conn;
    
    
    $adminID = ($userType === 'Admin') ? $userID : 'NULL';
    
    
    $actionDesc = "User logout: $userType ($email) logged out";
    
    
    $query = "INSERT INTO SystemLogs (AdminID, ActionDesc) VALUES ($adminID, '$actionDesc')";
    
    
    $result = mysqli_query($conn, $query);
    
    
    return ($result) ? true : false;
}

/**
 * Function to log general system activities
 * 
 * @param string $action 
 * @param int $adminID 
 * @return bool 
 */
function logSystemActivity($action, $adminID = NULL) {
    global $conn;
    
    
    $query = "INSERT INTO SystemLogs (AdminID, ActionDesc) VALUES (" . 
             ($adminID ? $adminID : "NULL") . ", '$action')";
    
    
    $result = mysqli_query($conn, $query);
    
    
    return ($result) ? true : false;
}
?>