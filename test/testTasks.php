<?php

declare (strict_types=1);
require_once __DIR__ . '/../src/tasks.php';   

/**
 * Funktion för att testa alla aktiviteter
 * @return string html-sträng med resultatet av alla tester
 */
function allaTaskTester(): string {
// Kom ihåg att lägga till alla testfunktioner
    $retur = "<h1>Testar alla uppgiftsfunktioner</h1>";
    $retur .= test_HamtaEnUppgift();
    $retur .= test_HamtaUppgifterSida();
    $retur .= test_RaderaUppgift();
    $retur .= test_SparaUppgift();
    $retur .= test_UppdateraUppgifter();
    return $retur;
}

/**
 * Funktion för att testa en enskild funktion
 * @param string $funktion namnet (utan test_) på funktionen som ska testas
 * @return string html-sträng med information om resultatet av testen eller att testet inte fanns
 */
function testTaskFunction(string $funktion): string {
    if (function_exists("test_$funktion")) {
        return call_user_func("test_$funktion");
    } else {
        return "<p class='error'>Funktionen $funktion kan inte testas.</p>";
    }
}

/**
 * Tester för funktionen hämta uppgifter för ett angivet sidnummer
 * @return string html-sträng med alla resultat för testerna 
 */
function test_HamtaUppgifterSida(): string {
    $retur = "<h2>test_HamtaUppgifterSida</h2>";
    try{
    //testa hämta delaktig sidnummer (-1) => 400
        $svar= hamtaSida(-1);
        if($svar->getStatus()===400) {
            $retur .="<p class='ok'> hämta felaktigt sidnummer (-1) gav förväntat svar 400</p>";
        } else {
            $retur .="<p class='ok'> hämta felaktigt sidnummer (-1) gav {$svar->getStatus()} " 
            . "istället för förväntat svar 400</p>";

        }

    // testa hämta giltigt sidnummer (1) => 200 + rätt egenskaper
    $svar=hamtaSida(1);
    if($svar->getStatus()!==200) {
            $retur .="<p class='ok'> hämta giltigt felaktigt sidnummer (-1) gav {$svar->getStatus()} " 
            . "istället för förväntat svar 200</p>";
    } else {
        $retur .="<p class='ok'> hämta giltigt sidnummer (-1) gav förväntat svar 200</p>";
        $result=$svar->getContent()->tasks;
        foreach ($result as $tasks) {
            if(!isset($tasks->id)){
                $retur .="<p class'error'>Egenskapen id saknas</p>";
                break;
            }
            if(!isset($tasks->activityId)){
                $retur .="<p class'error'>Egenskapen activityid saknas</p>";
                break;
            }
            if(!isset($tasks->activity)){
                $retur .="<p class'error'>Egenskapen activity saknas</p>";
                break;
            }
            if(!isset($tasks->date)){
                $retur .="<p class'error'>Egenskapen date saknas</p>";
                break;
            }
            if(!isset($tasks->time)){
                $retur .="<p class'error'>Egenskapen time saknas</p>";
                break;
            }
        }
    }

    //testa hämta fler stor sidnr => 200 + tom array
    $svar= hamtaSida(100);
    if($svar->getStatus()!==200) {
        $retur .="<p class='error'> hämta för stort sidnummer (100) gav {$svar->getStatus()} " 
        . "istället för förväntat svar 200</p>";
    } else {
        $retur .="<p class='ok'> hämta för stort (100) gav förväntat svar 200</p>";
        $resultat=$svar->getContent()->tasks;
        if(!$resultat===[]) {
            $retur .="<p class='error'> hämta för stort sidnummer ska inehålla en tom array för tasks<br>" 
            . print_r($resultat, true) . "<br>returnerades</p>";
        }
    }
    } catch (Exception $ex){
        $retur .= "<p class='error'>Något gick fel, meddelandet säger:<br> {$ex->getMessage()}</p>";
     }
    return $retur;
}

/**
 * Test för funktionen hämta uppgifter mellan angivna datum
 * @return string html-sträng med alla resultat för testerna
 */
function test_HamtaAllaUppgifterDatum(): string {
    $retur = "<h2>test_HamtaAllaUppgifterDatum</h2>";
    //testa fel ordning på datum 
    $datum1=new DateTimeImmutable();
    $datum2=new DateTime("yesterday");
    $svar= hamtaDatum($datum1, $datum2);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'> hämta fel ordning på datum gav förväntat svar 400</p>";
    } else {
        $retur .="<p class='error'> hämta fel ordning på datum gav {$svar->getStatus()} " 
        . "istället för förväntat svar 400</p>";

    }

    
    //testa datum utan poster => 200 och tom array för tasks
    $datum1=new DateTimeImmutable("1970-01-01");
    $datum2=new DateTimeImmutable("1970-01-01");
    $svar= hamtaDatum($datum1, $datum2);
    if($svar->getStatus()!==200) {
        $retur .="<p class='error'> hämta datum (1970-01-01 -- 1970-01-01) gav {$svar->getStatus()} " 
        . "istället för förväntat svar 200</p>";
    } else {
        $retur .="<p class='ok'> hämta datum (1970-01-01 -- 1970-01-01) gav förväntat svar 200</p>";
        $resultat=$svar->getContent()->tasks;
        if(!$resultat===[]) {
            $retur .="<p class='error'> hämta datum (1970-01-01 -- 1970-01-01) ska inehålla en tom array för tasks<br>" 
            . print_r($resultat, true) . "<br>returnerades</p>";
        }
    }



    //testa giltiga datum med poster => 200 och giltiga egenskaper
    if($svar->getStatus()!==200) {
        $retur .="<p class='error'> hämta poster för datum (1970-01-01 -- {$datum2->format('Y-m-d')} " 
                .  " gav {$svar->getStatus()} istället för förväntat svar 200</p>";
} else {
    $retur .="<p class='ok'> hämta poster för datum (1970-01-01 -- {$datum2->format('Y-m-d')} " 
    . " gav förväntat svar 200</p>";
    $result=$svar->getContent()->tasks;
    foreach ($result as $tasks) {
        if(!isset($tasks->id)){
            $retur .="<p class'error'>Egenskapen id saknas</p>";
            break;
        }
        if(!isset($tasks->activityId)){
            $retur .="<p class'error'>Egenskapen activityid saknas</p>";
            break;
        }
        if(!isset($tasks->activity)){
            $retur .="<p class'error'>Egenskapen activity saknas</p>";
            break;
        }
        if(!isset($tasks->date)){
            $retur .="<p class'error'>Egenskapen date saknas</p>";
            break;
        }
        if(!isset($tasks->time)){
            $retur .="<p class'error'>Egenskapen time saknas</p>";
            break;
        }
    }
}

    return $retur;
}

/**
 * Test av funktionen hämta enskild uppgift
 * @return string html-sträng med alla resultat för testerna
 */
function test_HamtaEnUppgift(): string {
    $retur = "<h2>test_HamtaEnUppgift</h2>";
    try{
        //Testa negativt tal
        $svar= hamtaEnskildUppgift(-1);
        if($svar->getStatus()===400){
            $retur .= "<p class='ok'>Hämta enskild med negativt tal ger förväntat svar 400</p>";
        } else {
            $retur .= "<p class='error'>Hämta enskild med negativt tal ger {$svar->getStatus()}"
            ."inte förväntat svar 400</p>";
        }
        //Testa för stort tal
        $svar= hamtaEnskildUppgift(100);
        if($svar->getStatus()===400){
            $retur .= "<p class='ok'>Hämta enskild med stort tal ger förväntat svar 400</p>";
        } else {
            $retur .= "<p class='error'>Hämta enskild med stort (100) tal ger {$svar->getStatus()}"
            ."inte förväntat svar 400</p>";
        }
        //Testa bokstäver
        $svar= hamtaEnskildUppgift((int) "sju");
        if($svar->getStatus()===400){
            $retur .= "<p class='ok'>Hämta enskild med bokstäver  ger förväntat svar 400</p>";
        } else {
            $retur .= "<p class='error'>Hämta enskild med bokstäver {'sju'} tal ger {$svar->getStatus()}"
            ."inte förväntat svar 400</p>";
        }
        //Testa giltigt tal
        $svar= hamtaEnskildUppgift(3);
        if($svar->getStatus()===200){
            $retur .= "<p class='ok'>Hämta enskild med med 3 ger förväntat svar 200</p>";
        } else {
            $retur .= "<p class='error'>Hämta enskild med enskild med 3 ger {$svar->getStatus()}"
            . "inte förväntat svar 200</p>";
        }
        }
    catch (Exception $ex){
   $retur .= "<p class='error'>Något gick fel, meddelandet säger:<br> [$ex->getMessage()]</p>";
}

return $retur;
}

/**
 * Test för funktionen spara uppgift
 * @return string html-sträng med alla resultat för testerna
 */
function test_SparanyUppgift(): string {
    $retur = "<h2>test_SparaUppgift</h2>";
    try{
    // testa allt  ok
    $igar=new DateTimeImmutable("yesterday");
    $imorgon=new DateTimeImmutable("tomorrow");
    $postData=["date"=>$igar->format('Y-m-d'),
        "time"=>"05:00",
        "activityId"=>1,
        "description"=>"Hurra vad bra"];
    $db= connectDb();
    $db->beginTransaction();
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===200) {
        $retur .="<p class='ok'>spara ny uppgift lyckades</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift misslyckades {$svar->getStatus()}"
        . "returnerades istället för förväntat 200</p>";
    }
    $db->rollback();
    //testa felaktigt (i morgon) =>400
    $postData["date"]=$imorgon->format("Y-m-d");
    $db->beginTransaction();
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades som förvänta (date = imorngon)</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift misslyckades {$svar->getStatus()}"
        . "returnerades istället för förväntat 400</p>";
    }
    $db->rollback();

    // testa felaktigt datumformat =>400
    $postData["date"]=$imorgon->format("d.m.Y");
    $db->beginTransaction();
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades som förväntat (felaktigt datunformat)</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift med felaktigt datumFormat"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();
    //testa datum saknas =>400 
    $db->beginTransaction();
    unset($postData["date"]);
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades som förväntat (datum saknas)</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift utan datum"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();
    // testa felaktig tid (12 timmar) =>400
    $db->beginTransaction();

    $postData["date"]=$igar->format('Y-m-d');
    $postData["time"]="12:00";
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades som förväntat (felaktig tid 12:00)</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift med felaktigt tid (12:00)"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();
    // testa felaktigt tidsformat =>400
    $db->beginTransaction();
    $postData["date"]=$igar->format('Y-m-d');
    $postData["time"]="5_30";
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades som förväntat (felaktig tidsforma)</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift med felaktigt tidsformat"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();

    // testa tid saknas =>400
    $db->beginTransaction();
    unset($postData["time"]);
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades som förväntat (time saknas)</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift utan tid"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();
    // testa description saknas =>200
    unset ($postData["descirption"]);
    $postData["time"]="3:15";
        $db->beginTransaction();
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===200) {
        $retur .="<p class='ok'>spara ny uppgift  misslyckades utan desctiption lyckades</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift utan description"
        . "returnerade {$svar->getStatus()} istället för förväntat 200</p>";
    }
    $db->rollback();
    // testa aktivitetsid felaktigt (-1) =>400
    $postData["activityId"]=-1;
    $db->beginTransaction();
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  med felaktigt activityId (-1) misslyckades, som förväntat</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift utan aktctivityId (-1)"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();
    // testa aktivitetsid som saknas (100) =>400
    $postData["activityId"]=100;
    $db->beginTransaction();
    $svar=sparaNyUppgift($postData);
    if($svar->getStatus()===400) {
        $retur .="<p class='ok'>spara ny uppgift  med felaktigt activityId (100) misslyckades, som förväntat</p>";
    } else {
        $retur .= "<p class='error'>spara ny uppgift  med felaktigt aktctivityId (100)"
        . "returnerade {$svar->getStatus()} istället för förväntat 400</p>";
    }
    $db->rollback();


    } catch (Exception $ex) {
        $retur .=$ex->getMessage();
    }
    return $retur;
}

/**
 * Test för funktionen uppdatera befintlig uppgift
 * @return string html-sträng med alla resultat för testerna
 */
function test_UppdateraUppgifter(): string {
    $retur = "<h2>test_UppdateraUppgifter</h2>";
    $retur .= "<p class='ok'>Testar uppdatera uppgift</p>";
    return $retur;
}

/**
 * Test för funktionen radera uppgift
 * @return string html-sträng med alla resultat för testerna
 */
function test_RaderaUppgift(): string {
    $retur = "<h2>test_RaderaUppgift</h2>";
    $retur .= "<p class='ok'>Testar radera uppgift</p>";
    return $retur;
}
