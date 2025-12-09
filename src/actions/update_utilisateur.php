<?php

require_once __DIR__ . "/../template/layout.php";

$action = $_POST['action'] ?? '';

try {
    switch ($action) {

        case "name":
            $new_name = $_POST["modif_name"];

            if(strlen($new_name) > 50 || empty($new_name)) {

                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur",
                "erreur_modif_nom"=>"La taille du nom doit être inférieure à 50 caractères",
                "name"=>$name]);

                die();
            }

            try {

                $stmt = Database::instance()->prepare("UPDATE Utilisateur SET nom = ? WHERE email = ?");
                $stmt->execute([$new_name, Auth::user()]);

                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur"]);

                die();
            }
            catch (PDOException $e) {
                die();
            }

            break;

        case "first_name":
            $new_first_name = $_POST["modif_first_name"];

            if(strlen($new_first_name) > 50 || empty($new_first_name)) {

                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur",
                "erreur_modif_prenom"=>"La taille du prénom doit être inférieure à 50 caractères",
                "first_name"=>$first_name]);

                die();
            }

            try {

                $stmt = Database::instance()->prepare("UPDATE Utilisateur SET prenom = ? WHERE email = ?");
                $stmt->execute([$new_first_name, Auth::user()]);

                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur"]);

                die();
            }
            catch (PDOException $e) {
                die();
            }

            break;

        case "password":
            $old_password = $_POST["modif_password_old"];
            $new_password = $_POST["modif_password1"];
            $new_password_verif = $_POST["modif_password2"];
            
            if(empty($old_password) || empty($new_password) || empty($new_password_verif)) {

                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur",
                "erreur_modif_password"=>"Veuillez compléter tous les champs."]);

                die();
            }

            if($new_password != $new_password_verif) {
                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur",
                "erreur_modif_password"=>"Les mots de passes ne sont pas identiques."]);

                die();
            }

            $stmt = Database::instance()->prepare("SELECT mot_de_passe FROM Utilisateur WHERE email = :email");
            $stmt->execute(["email"=>Auth::user()]);
        
            $row = $stmt->fetch();

            if (!$row) {
                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur",
                "erreur_modif_password"=>"Une erreur est survenue."]);

                die(); // Ne devrait jamais arriver à priori
            }

            if(!Auth::verify_password($old_password, $row["mot_de_passe"])) {
                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur",
                "erreur_modif_password"=>"Mot de passe original incorrect."]);

                die();
            }

            $new_password = Auth::hash_password($new_password);

            try {

                $stmt = Database::instance()->prepare("UPDATE Utilisateur SET mot_de_passe = ? WHERE email = ?");
                $stmt->execute([$new_password, Auth::user()]);

                render_page("update_utilisateur.php", ["title"=>"Finance App - MAJ utilisateur"]);
                
                die();
            }
            catch (PDOException $e) {
                die();
            }

            break;
    }
}
catch (Exception $e) {
    return false;
}