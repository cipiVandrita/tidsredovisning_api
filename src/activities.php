<?php

declare (strict_types=1);
require_once __DIR__ .  './funktioner.php';

/**
 * Läs av rutt-information och anropa funktion baserat på angiven rutt
 * @param Route $route Rutt-information
 * @param array $postData Indata för behandling i angiven rutt
 * @return Response
 */
function activities(Route $route, array $postData): Response {
    var_dump($route, $postData);
    try {
        if (count($route->getParams()) === 0 && $route->getMethod() === RequestMethod::GET) {
            return hamtaAllaAktiviteter();
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::GET) {
            return hamtaEnskildAktivitet((int) $route->getParams()[0]);
        }
        if (isset ($postData["activity"]) && count($route->getParams()) === 0 && 
            $route->getMethod() === RequestMethod::POST) {
            return sparaNyAktivitet((string) $postData["activity"]);
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::PUT) {
            return uppdaterAaktivitet((int) $route->getParams()[0], (string) $postData["activity"]);
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::DELETE) {
            return raderaAktivitet((int) $route->getParams()[0]);
        }
    } catch (Exception $exc) {
        return new Response($exc->getMessage(), 400);
    }

    return new Response("Okänt anrop", 400);
}

/**
 * Returnerar alla aktiviteter som finns i databasen
 * @return Response
 */
function hamtaAllaAktiviteter(): Response {
    //koppla mot databasen
    $db=connectDb();

    // hämta alla poster från tabellen
    $resultat=$db->query("SELECT id, kategori from kategorier");

    //lägga in posterna från tabellen
    $retur=[];
    while($row=$resultat->fetch()){
        $post=new stdClass();
        $post->id=$row['id'];
        $post->activity=$row['kategori'];
        $retur[]=$post;
    }
    $out= new stdClass();
    $out->activities=$retur;
 
    //returnera svaret
    return new Response($out, 200);    
}

/**
 * Returnerar en enskild aktivitet som finns i databasen
 * @param int $id Id för aktiviteten
 * @return Response
 */
function hamtaEnskildAktivitet(int $id): Response {
    // kontrollera indata
    $kollatID=filter_var($id, FILTER_VALIDATE_INT);
    if(!$kollatID || $kollatID < 1){   
        $out=new stdClass();
        $out->error=["Felaktig indata", "$id är inget heltal"];
        return new Response($out,400 );
    }
    //koppla databas och hämta post
    $db= connectDb();
    $stmt=$db->prepare("SELECT id, kategori FROM kategorier where id=:id");
    if (!$stmt->execute(["id"=>$kollatID])) {
        $out=new stdClass();
        $out->error=["Fel vid läsning från databasen". implode(",", $stmt->errorInfo())];
        return new Response($out,400);
    }
 //sätt utdata returnera
    if($row=$stmt->fetch()){
        $out=new stdClass();
        $out->id=$row["id"];
        $out->activity=$row["kategori"];
        return new Response($out);

    } else {
        $out=new stdClass();
        $out->error=["Hittade inget post med id=$kollatID"];
        return new Response($out, 400);
    }
}

/**
 * Lagrar en ny aktivitet i databasen
 * @param string $aktivitet Aktivitet som ska sparas
 * @return Response
 */
function sparaNyAktivitet(string $aktivitet): Response {
    
        //kontrollera indata
        $kontrolleradAktivitet= filter_var($aktivitet, FILTER_SANITIZE_ENCODED);
        $kontrolleradAktivitet= trim($kontrolleradAktivitet);
        if($kontrolleradAktivitet==="") {
            $out=new stdClass();
            $out->error=["Fel vid spara", "activity kan inter vara tom"];
             return new Response($out, 400);
        }
 
    
        //koppla mot databas
        $db= connectDb();       
        try {

        //uppdateraaktivitet post
        $stmt=$db->prepare("INSERT INTO kategorier (kategori) VALUES (:kategori)");
        $stmt->execute(["kategori"=>$kontrolleradAktivitet]);
        $antalPoster=$stmt->rowCount();
        

        //returnera svar
        if($antalPoster>0){
            $out=new stdClass();
            $out->message=["sparning lyckades", "$antalPoster post(er) lades till"];
            $out->id=$db->lastInsertId();
            return new Response($out);

        } else {
            $out=new stdClass();
            $out->error=["något gick fel vid spara", implode(",", $db->errorInfo())];
            return new Response($out, 400);

        }
    }catch (Exception $ex) {
        $out = new stdClass();
        $out->error = ["något gick fel vid spara" , $ex->getMessage()];
        return new Response($out, 400);
    }
    

}

/**
 * Uppdaterar angivet id med ny text
 * @param int $id Id för posten som ska uppdateras
 * @param string $aktivitet Ny text
 * @return Response
 */
function uppdateraaktivitet(int $id, string $aktivitet): Response {
    
        // kontrollera indata
    $kollatID=filter_var($id, FILTER_VALIDATE_INT);
    if(!$kollatID || $kollatID < 1){   
        $out=new stdClass();
        $out->error=["Felaktig indata", "$id är inget heltal"];
        return new Response($out,400 );
    }
    $kontrolleradAktivitet= filter_var($aktivitet, FILTER_SANITIZE_ENCODED);
        $kontrolleradAktivitet= trim($kontrolleradAktivitet);
        if($kontrolleradAktivitet==="") {
            $out=new stdClass();
            $out->error=["Fel vid uppdatering", "activity kan inter vara tom"];
             return new Response($out, 400);
        }
 
    try {
        //koppla mot databas
        $db= connectDb();       
        

        //uppdateraaktivitet post
        $stmt=$db->prepare(" UPDATE kategorier "
                . " SET kategori=:aktivitet"
                . " WHERE id=:id");
        $stmt->execute(["aktivitet"=>$kontrolleradAktivitet, "id" => $kollatID]);
        $antalPoster=$stmt->rowCount();
        

        //returnera svar 
        $out=new stdClass();
        if($antalPoster>0){
            $out->result = true;
            $out->message=["uppdatering lyckades", "$antalPoster poster uppdaterades"];

        } else {
            $out->result = false;
            $out->message = ["uppdatering lyckades", "0  Poster uppdaterades"];

        }
        return new Response($out, 200);
    }catch (Exception $ex) {
        $out = new stdClass();
        $out->error = ["något gick fel vid uppdatering" , $ex->getMessage()];
        return new Response($out, 400);
    }

}
    



/**
 * Raderar en aktivitet med angivet id
 * @param int $id Id för posten som ska raderas
 * @return Response
 */
function raderaAktivitet(int $id): Response {
    // kontrollera id
     $kollatID=filter_var($id, FILTER_VALIDATE_INT);
     if(!$kollatID || $kollatID < 1){   
         $out=new stdClass();
         $out->error=["Felaktig indata", "$id är inget heltal"];
         return new Response($out,400 );
     }
   
try{
    //koppla mot databas
    $db= connectDb();       
        

    //skicka raderaAktivitiet-kommando
    $stmt=$db->prepare("DELETE FROM kategorier"
            . " WHERE id=:id");
    $stmt->execute(["id"=>$kollatID]);
    $antalPoster=$stmt->rowCount();
    
    //kontrollera databas-svar och skapa utdata-svar
    $out=new stdClass();
    if($antalPoster>0) {
        $out->result=true;
        $out->message=["raderaAktivitiet lyckades", "$antalPoster post(er) raderades"];

    } else {
        $out->result=false;
        $out->message=["raderaAktivitiet misslyckades", "inga poster raderades"];
    }

    return new Response($out);

    }catch (Exception $ex) {
        $out = new stdClass();
        $out->error = ["något gick fel vid raderaAktivitiet" , $ex->getMessage()];
        return new Response($out, 400);
    }

}
