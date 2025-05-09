<?php

require_once 'database.php';

class Clubs {
    private $db;

    public $clubID;
    public $clubName;
    public $clubDescription;
    public $clubMinAge;
    public $clubMaxAge;
    public $librarianIDs;

    public function __construct() {
        $this->db = new Database();
        $this->librarianIDs = [];
    }

    public function add() {
        $conn = $this->db->connect();

        $stmt = $conn->prepare("INSERT INTO club (clubName, clubDescription, clubMinAge, clubMaxAge) VALUES (:clubName, :clubDescription, :clubMinAge, :clubMaxAge)");
        $stmt->bindParam(':clubName', $this->clubName);
        $stmt->bindParam(':clubDescription', $this->clubDescription);
        $stmt->bindParam(':clubMinAge', $this->clubMinAge);
        $stmt->bindParam(':clubMaxAge', $this->clubMaxAge);

        if ($stmt->execute()) {
            $this->clubID = $conn->lastInsertId();

            foreach ($this->librarianIDs as $librarianID) {
                $stmt = $conn->prepare("INSERT INTO club_management (clubID, librarianID) VALUES (:clubID, :librarianID)");
                $stmt->bindParam(':clubID', $this->clubID);
                $stmt->bindParam(':librarianID', $librarianID);
                $stmt->execute();
            }

            return true;
        } else {
            return false;
        }
    }

    public function edit($clubID, $clubName, $clubDescription, $librarianIDs, $clubMinAge, $clubMaxAge) {
        $conn = $this->db->connect();
    
        // Start a transaction
        $conn->beginTransaction();
    
        try {
            // Update club details
            $sql = "UPDATE club SET clubName = :clubName, clubDescription = :clubDescription, clubMinAge = :clubMinAge, clubMaxAge = :clubMaxAge WHERE clubID = :clubID";
            $query = $conn->prepare($sql);
            $query->bindParam(':clubID', $clubID);
            $query->bindParam(':clubName', $clubName);
            $query->bindParam(':clubDescription', $clubDescription);
            $query->bindParam(':clubMinAge', $clubMinAge);
            $query->bindParam(':clubMaxAge', $clubMaxAge);
            $query->execute();
    
            // Delete existing club management records
            $deleteStmt = $conn->prepare("DELETE FROM club_management WHERE clubID = :clubID");
            $deleteStmt->bindParam(':clubID', $clubID);
            $deleteStmt->execute();
    
            // Insert new club management records
            foreach ($librarianIDs as $librarianID) {
                $insertStmt = $conn->prepare("INSERT INTO club_management (clubID, librarianID) VALUES (:clubID, :librarianID)");
                $insertStmt->bindParam(':clubID', $clubID);
                $insertStmt->bindParam(':librarianID', $librarianID);
                $insertStmt->execute();
            }
    
            // Commit the transaction
            $conn->commit();
            return true;
        } catch (PDOException $e) {
            // Roll back the transaction on error
            $conn->rollback();
            return false;
        }
    }
    
    
    function is_name_exist(){
        $sql = "SELECT * FROM club WHERE clubName = :clubName;";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':clubName', $this->clubName);
        $query->execute();
        if ($query->rowCount() > 0) {
            return true; // Club name exists
        } else {
            return false; // Club name doesn't exist
        }
    }
    

    public function fetch($clubID) {
        // Get club details
        $stmt = $this->db->connect()->prepare("SELECT * FROM club WHERE clubID = :clubID");
        $stmt->bindParam(':clubID', $clubID, PDO::PARAM_INT);
        $stmt->execute();
        $club = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$club) {
            return false;
        }
        
        // Get associated librarians
        $stmt = $this->db->connect()->prepare("SELECT librarianID FROM club_management WHERE clubID = :clubID");
        $stmt->bindParam(':clubID', $clubID, PDO::PARAM_INT);
        $stmt->execute();
        $librarianIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $club['librarianIDs'] = $librarianIDs;
        return $club;
    }
    

    function delete($clubID)
    {
        $sql = "DELETE FROM club WHERE clubID = :clubID";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':clubID', $clubID);

        return $query->execute();
    }
    
    function getClubManagers($clubID)
    {
        $sql = "SELECT librarian.* FROM club_management 
                JOIN librarian ON club_management.librarianID = librarian.librarianID 
                WHERE club_management.clubID = :clubID";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':clubID', $clubID);
        if ($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data;
    }

    public function getClubById($clubID) {
        $sql = "SELECT * FROM club WHERE clubID = :clubID";
        $params = array(':clubID' => $clubID);
        $result = $this->db->select($sql, $params);
        return !empty($result) ? $result[0] : null;
    }
    
    function fetchLibrarian($librarianID)
    {
        $sql = "SELECT * FROM librarian WHERE librarianID = :librarianID;";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':librarianID', $librarianID);
        if ($query->execute()) {
            $data = $query->fetch();
        }
        return $data;
    }


    function show()
    {
        $sql = "SELECT * FROM club ORDER BY clubCreatedAt DESC;";
        $query = $this->db->connect()->prepare($sql);
        $data = null;
        if ($query->execute()) {
            $data = $query->fetchAll();
        }
        return $data ?: []; // Return an empty array if $data is falsy
    }
    
    function getMemberCount($clubID){
        $sql = "SELECT COUNT(*) AS member_count FROM club_membership WHERE clubID = :clubID AND cmstatus = 'Approved'";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':clubID', $clubID);
        $memberCount = 0;
        if ($query->execute()) {
            $result = $query->fetch(PDO::FETCH_ASSOC);
            $memberCount = $result['member_count'];
        }
        return $memberCount;
    }   
    function getClubMembers($clubID){
        $sql = "SELECT CONCAT(u.userFirstName, ' ', u.userMiddleName, ' ', u.userLastName) AS fullName,
                    u.userEmail,
                    u.userContactNo,
                    u.userGender,
                    CONCAT(u.userStreetName, ', ', u.userBarangay, ', ', u.userCity, ', ', u.userProvince, ', ', u.userZipCode) AS address,
                    cm.cmCreatedAt AS dateJoined
                FROM club_membership cm
                JOIN user u ON cm.userID = u.userID
                WHERE cm.clubID = :clubID AND cm.cmstatus = 'Approved'"; // Corrected cm.cmstatus
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':clubID', $clubID);
        $data = null;
        
        if ($query->execute()) {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    }


    function getAllClubs() {
        $sql = "SELECT * FROM club";
        $query = $this->db->connect()->prepare($sql);
    
        $clubs = null;
    
        if ($query->execute()) {
            $clubs = $query->fetchAll();
        }
    
        return $clubs;
    }

    
    
}

?>
