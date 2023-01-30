<?php

declare (strict_types=1);


/**
 * Hämtar en lista med alla uppgifter och tillhörande aktiviteter 
 * Beroende på indata returneras en sida eller ett datumintervall
 * @param Route $route indata med information om vad som ska hämtas
 * @return Response
 */
function tasklists(Route $route): Response {
    try {
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::GET) {
            return hamtaSida((int) $route->getParams()[0]);
        }
        if (count($route->getParams()) === 2 && $route->getMethod() === RequestMethod::GET) {
            return hamtaDatum(new DateTimeImmutable($route->getParams()[0]), new DateTimeImmutable($route->getParams()[1]));
        }
    } catch (Exception $exc) {
        return new Response($exc->getMessage(), 400);
    }

    return new Response("Okänt anrop", 400);
}

/**
 * Läs av rutt-information och anropa funktion baserat på angiven rutt
 * @param Route $route Rutt-information
 * @param array $postData Indata för behandling i angiven rutt
 * @return Response
 */
function tasks(Route $route, array $postData): Response {
    try {
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::GET) {
            return hamtaEnskildUppgift((int) $route->getParams()[0]);
        }
        if (count($route->getParams()) === 0 && $route->getMethod() === RequestMethod::POST) {
            return sparaNyUppgift($postData);
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::PUT) {
            return uppdateraUppgift((int) $route->getParams()[0], $postData);
        }
        if (count($route->getParams()) === 1 && $route->getMethod() === RequestMethod::DELETE) {
            return raderaUppgift((int) $route->getParams()[0]);
        }
    } catch (Exception $exc) {
        return new Response($exc->getMessage(), 400);
    }

    return new Response("Okänt anrop", 400);
}

/**
 * Hämtar alla uppgifter för en angiven sida
 * @param int $sida
 * @return Response
 */
function hamtaSida(int $sida): Response {
    $posterPerSida=3;
    //kolla id ok

    $kollatSidnr=filter_var($sida, FILTER_VALIDATE_INT);
    if(!$kollatSidnr || $kollatSidnr<1) {
        $out= new stdClass();
        $out->error=["Felaktikt sidnummer ($sida) angivet", "Läsning misslyckades"];
        return new Response($out, 400);
    }

    //koppla databas 
    $db=connectDb();


    //hämta antal poster
   $stmt=$db->query("SELECT COUNT(*) FROM uppgifter");
   if($row=$stmt->fetch()) {
    $antalPoster=$row[0];

   }
   $antalSidor=ceil($antalPoster/$posterPerSida);

    //hämta aktuella poster
    $first=($kollatSidnr-1)*$posterPerSida;
    $result=$db->query("SELECT  t.id, Kategoriid, datum, tid, beskrivning, kategori "
                . " FROM uppgifter t "
                . " INNER JOIN kategorier a ON kategoriid=a.id "
                . " ORDER BY Datum asc "
                . " LIMIT $first, $posterPerSida");

    //loopa resultatsettet och skapa utdata
    $records=[];
    while($row=$result->fetch()) {
        $rec=new stdClass();
        $rec->id=$row["id"];
        $rec->activityId=$row["Kategoriid"];
        $rec->activity=$row["kategori"];
        $rec->date=$row["datum"];
        $rec->time=substr($row["tid"], 0, 5);
        $rec->description=$row["beskrivning"];
        $records[]=$rec;    
    }

    //returnera utdata
    $out=new stdClass();
    $out->pages=$antalSidor;
    $out->tasks=$records;

    return new Response($out);
}

/**
 * Hämtar alla poster mellan angivna datum
 * @param DateTimeInterface $from
 * @param DateTimeInterface $tom
 * @return Response
 */
function hamtaDatum(DateTimeInterface $from, DateTimeInterface $tom): Response {
    //kolla indata
    if($from->format('Y-m-d')>$tom->format('Y-m-d')) {
        $out=new stdClass();
        $out->error=["Felaktif indata", "Från-datum ska vara mindre an till-datum"];
        return new response($out, 400);
    }


    //koppla databas
    $db= connectDb();

    //hämta poster
    $stmt=$db->prepare("SELECT  t.id, Kategoriid, datum, tid, beskrivning, kategori "
    . " FROM uppgifter t "
    . " INNER JOIN kategorier a ON kategoriid=a.id "
    . " WHERE datum between :from AND :to "
    . " ORDER BY Datum asc ");
    $stmt->execute(["from"=>$from->format('Y-m-d'), "to"=>$tom->format('Y-m-d')]);

    //looba resultatsettet och skapa uitada
    $records=[];
    while($row=$stmt->fetch()) {
        $rec=new stdClass();
        $rec->id=$row["id"];
        $rec->activityId=$row["Kategoriid"];
        $rec->activity=$row["kategori"];
        $rec->date=$row["datum"];
        $rec->time=substr($row["tid"], 0, 5);
        $rec->description=$row["beskrivning"];
        $records[]=$rec;    
    }

    //returnera utdata
    $out=new stdClass();
    $out->tasks=$records;

    return new Response($out);
}

/**
 * Hämtar en enskild uppgiftspost
 * @param int $id Id för post som ska hämtas
 * @return Response
 */
function hamtaEnskildUppgift(int $id): Response {
    return new Response("Hämta task $id", 200);
}

/**
 * Sparar en ny uppgiftspost
 * @param array $postData indata för uppgiften
 * @return Response
 */
function sparaNyUppgift(array $postData): Response {
    // kolla indata
    $check=kontrolleraIndata($postData);
    if($check!==""){
        $out=new stdClass;
        $out-> error=["Felaktig indata", $check];
        return new Response($out, 400);
    }

    //koppla mot db
    $db= connectDb();
    if(!isset($postData["description"])) {
        $postData["description"]="";
    }

    //förbered och exekvera sql
    $stmt=$db->prepare(" INSERT INTO uppgifter"
            . " (datum, tid, kategoriid , beskrivning) "
            . " VALUES (:date, :time, :activityId, :description)");
    $stmt->execute(["date"=>$postData["date"],
        "time"=>$postData["time"], 
        "activityId"=>$postData["activityId"],
        "description"=>$postData["description"]]);
    //konrollera svar - skicka tillbaka utdata
    $antalPoster=$stmt->rowCount();
    if($antalPoster>0) {
        $out=new stdClass();
        $out->id=$db->lastInsertId();
        $out->message=["spara ny uppgift lyckades"];
        return new Response ($out);
    } else {
        $out=new stdClass();
        $out->error=["spara ny uppgift misslyckades"];
        return new Response($out, 400);
    }

}

/**
 * Uppdaterar en angiven uppgiftspost med ny information 
 * @param int $id id för posten som ska uppdateras
 * @param array $postData ny data att sparas
 * @return Response
 */
function uppdateraUppgift(int $id, array $postData): Response {
    return new Response("Uppdaterar task $id", 200);
}

/**
 * Raderar en uppgiftspost
 * @param int $id Id för posten som ska raderas
 * @return Response
 */
function raderaUppgift(int $id): Response {
    return new Response("Raderar task $id", 200);
}


function kontrolleraIndata(array $postData):string {
    try{
        // kontrollera giltigt datum
        if(!isset ($postData["date"])) {
            return "datum saknas (date)";
        }
        $datum= DateTimeImmutable::createFromFormat("Y-m-d", $postData["date"]);
        if(!$datum || $datum->format('Y-m-d')>date("Y-m-d")) {
            return "ogiltigt datum";
        }
        // kontrollera giltig tid
        if(!isset ($postData["time"])) {
            return "datum saknas (time)";
        }
        $tid= DateTimeImmutable::createFromFormat("H:i", $postData["time"]);
        if(!$tid || $tid->format("H:i")>"08:00"){
            return "ogiltig tid (time)";
        }
        //kontrollera aktivitets id
        $aktivitetsId=filter_var($postData["activityId"], FILTER_VALIDATE_INT);
        if(!$aktivitetsId || $aktivitetsId<1) {
            return "ogiltigt aktivitetsid (activityId)";
        }
        $svar= hamtaEnskildAktivitet($aktivitetsId);
        if($svar->getStatus()!==200) {
            return "Angivet aktivitetsid saknas";
        }
        return "";
    } catch (Exception $exc) {
        return $exc -> getMessage();
    }
}