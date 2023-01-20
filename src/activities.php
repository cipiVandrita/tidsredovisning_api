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
    try {
        if (count($route->getParams()) === 0 && $route->getMethod() === RequestMethod::GET) {
            return hamtaAlla();
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::GET) {
            return hamtaEnskild((int) $route->getParams()[0]);
        }
        if (isset ($postData["activity"]) && count($route->getParams()) === 0 && 
            $route->getMethod() === RequestMethod::POST) {
            return sparaNy((string) $postData["activity"]);
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::PUT) {
            return uppdatera((int) $route->getParams()[0], (string) $postData["activity"]);
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::DELETE) {
            return radera((int) $route->getParams()[0]);
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
function hamtaAlla(): Response {
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
 
    //returnera svaret
    return new Response($retur, 200);    
}

/**
 * Returnerar en enskild aktivitet som finns i databasen
 * @param int $id Id för aktiviteten
 * @return Response
 */
function hamtaEnskild(int $id): Response {
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

    if($row=$stmt->fetch()){
        $out=new stdClass();
        $out->id=$row["id"];
        $out->activity=$row["kategori"];
        return new Response($out);

    } else {
        $out=new stdClass();
        $out->error=["Hittade inger post med id=$kollatID"];
        return new Response($out, 400);
    }

    
    //sätt utdata

    //Returnera utdata
    return new Response("Hämta aktivitet $id", 200);
}

/**
 * Lagrar en ny aktivitet i databasen
 * @param string $aktivitet Aktivitet som ska sparas
 * @return Response
 */
function sparaNy(string $aktivitet): Response {
    
        //kontrollera indata
        $kontrolleradAktivitet= filter_var($aktivitet, FILTER_SANITIZE_ENCODED);
        $kontrolleradAktivitet= trim($kontrolleradAktivitet);
        if($kontrolleradAktivitet==="") {
            $out=new stdClass();
            $out->error=["Fel vid uppdatering", "activity kan inter vara tom"];
             return new Response($out, 400);
        }
 
    
        //koppla mot databas
        $db= connectDb();       
        try {

        //uppdatera post
        $stmt=$db->prepare("INSERT INTO kategorier (kategori) VALUES (:kategori)");
        $stmt->execute(["kategori"=>$kontrolleradAktivitet]);
        $antalPoster=$stmt->rowCount();
        

        //returnera svar
        if($antalPoster>0){
            $out=new stdClass();
            $out->message=["uppdatering lyckades", "$antalPoster post(er) lades till"];
            $out->id=$db->lastInsertId();
            return new Response($out);

        } else {
            $out=new stdClass();
            $out->error=["något gick fel vid uppdatering", implode(",", $db->errorInfo())];
            return new Response($out, 400);

        }
    }catch (Exception $ex) {
        $out = new stdClass();
        $out->error = ["något gick fel vid uppdatering" , $ex->getMessage()];
        return new Response($out, 400);
    }
    

}

/**
 * Uppdaterar angivet id med ny text
 * @param int $id Id för posten som ska uppdateras
 * @param string $aktivitet Ny text
 * @return Response
 */
function uppdatera(int $id, string $aktivitet): Response {
    
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
        

        //uppdatera post
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
function radera(int $id): Response {
    return new Response("Raderar aktivitet $id", 200);
}
