#!/usr/bin/php
<?php

// Author: Heinz Peter Hippenstiel, github.hph@xoxy.net
// Das Skript muss als cronjob alle ADDMINUTES Minuten gestartet werden.
// Es prüft ob der Computer eine erlaubte Zeit angeschaltet war
// und fährt den Computer runter falls die Zeit überschritten wurde.
// Die Prüfung kann übersprungen werden, wenn eine bestimmte Datei
// vorhanden ist. Standardmäßig kann jeder die Datei erzeugen
// weil in /var/tmp gecheckt wird. GGF umbiegen auf zB /root

// */5 * * * * /home/user/kids_hours.php

define('DEBUG', true);

// Erlaubte Zeit - je nach Rechner
define('MAXTIME', (gethostname() == 'pc-kind') ? 14*60 : 5*60);

// Welcher Benutzer
//define('NOTIFYUSER', (gethostname() == 'pc-kind') ? 'tochter' : 'sohn');
// evaluiert den angemeldeten Benutzer (geht nur mit einem angemeldeten Benutzer)
define('NOTIFYUSER', exec("w|grep -oP '\w+(?= +tty)'"));

// Zu wieviel übrigen Minuten soll Notify gesendet werden?
define('INFOTIME', "5,10,15,30,60");

// Wieviele Minuten hinzufügen - sollte mit crontab Eintrag passen
// Achtung - wenn das Fenster zu klein ist (zB 1 Minute) könnte es schwierig werden
// das System zurück zu setzen
define('ADDMINUTES', 5);

// Files
define('CSVFILE', '/var/tmp/kids_hours');

// Skip checks
define('SKIP', (file_exists(CSVFILE.'_skip')) ? false : true);

DEBUG && print "max_time: ".MAXTIME."\n";
DEBUG && print "user: ".NOTIFYUSER."\n";
DEBUG && print (SKIP)?"skip: false\n":"skip: true\n";

// Funktion:  Lese CSV Datei und schreibe alle Elemente in ein Array
// Input:     kein
// Output:    Array mit allen Elementen aus CSV Datei, Komma getrennt.
//            Falls Datei nicht existiert wird ein Array mit zwei null Elementen geliefert.
function read_eval()
{
  // Default
  $r = [null,null];
  $c = (file_exists(CSVFILE)) ? rtrim(file_get_contents(CSVFILE)) : null;

  if ($c)
  {
    $r = explode(',',$c);
  }
  DEBUG && print "read_eval: <".implode(',',$r).">\n";
  return $r;
}

// Funktion:  Schreib Array in CSV Datei, Komma getrennt.
// Input:     Array
// Output:    keiner
function write_eval($r)
{
  DEBUG && print "write_eval: <".implode(',',$r).">\n";
  file_put_contents(CSVFILE,implode(',',$r));
  return;
}

// Funktion:  Evaluiere Zeitstatus
// Input:     kein
// Output:    kein
function check_eval()
{
  $c = read_eval(CSVFILE);
  // Default - Kalenderwoche, Zeit, Status
  $r = [date('W'),0,'undefined'];
  // Wenn Datumsformat gleich, erhöhe um ADDMINUTES Minuten
  if($r[0] == $c[0])
  {
    $r[1] = $c[1] + ADDMINUTES;
  }
  // Skip inaktiv
  if(SKIP)
  {
    // Zeit abgelaufen?
    if($r[1] >= MAXTIME)
    {
      // Shutdown Poweroff
      $o = 'nohup /sbin/shutdown -P +'.ADDMINUTES.' > /dev/null 2>&1 & echo $!';
      exec($o,$op);
      $r[2] = 'shutdown with pid '.(int)$op[0];
      // Notify
      $m = 'Deine erlaubte Computer Zeit von '.MAXTIME.' Minuten\n';
      $m .= 'ist für diese Woche abgelaufen.\n\n';
      $m .= 'Der Computer schaltet sich in '.ADDMINUTES.' Minuten aus.\n';
      send_notify("WARNUNG",$m);
      DEBUG && print "check_eval: shutdown\n";
    }
    else
    {
      $r[2] = 'counting';
      DEBUG && print "check_eval: counting\n";
      // n-Minuten vor Ablauf der Zeit eine Info senden
      $t = MAXTIME-$r[1];
      // Suche Zeit in Array
      if(in_array($t, explode(',',INFOTIME)))
      {
        $m = 'Du hast noch '.$t.' Minuten Zeit für diese Woche.\n';
        send_notify("INFO",$m);
        DEBUG && print "check_eval: notify minutes $t\n";
      }
    }
  }
  else
  {
    $r[2] = 'skipped';
    DEBUG && print "check_eval: skipped\n";
  }
  write_eval($r);
  return;
}

// Funktion:  Sende Notify an User
// Input:     Überschrift (String)
//            Nachricht (String)
// Output:    kein
function send_notify($h,$m)
{
  $o = 'su -c \'DISPLAY=:0 notify-send -u critical';
  $o .= ' "'.$h.'" "'.$m.'"\' '.NOTIFYUSER;
  exec($o);
  return;
}

check_eval();
