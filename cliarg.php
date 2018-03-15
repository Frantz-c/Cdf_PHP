<?php

/*
** "cliarg.php"
**
** Tableau "$arg" = [
**  'b'   => 'booleen', // Argument court => Argument long : 1 bool
**  'r:'  => '#',       // Argument court => None          : 1 arg
**  's+,' => 'size'     // Argument court => Argument long : n arguments séparés par des virgules
** ];
** 
** Valeur de retour de la méthode Cliarg::check(array $e) : 
** - -1 si le tableau d'arguments attendus contient une erreur
** - -2 s'il y a une erreur de la part de l'utilisateur
*/


define('USR_ERROR', -1);
define('ARG_ERROR', -2);



class Cliarg
{
  private $usrarg;
  private $cliarg;

  public function __construct(array $usrarg, array $cliarg)
  {
    $this->usrarg = $usrarg;
    array_shift($cliarg);
    $this->cliarg = $cliarg;
  }

  public function check(array &$error)
  {
    $ret = $this->printUserError();
    if ($ret === USR_ERROR) return $ret;
    $values = [];
    $ret = $this->getValues($error, $values);
    $this->setBools($values);
    return ($ret === true) ? $values: $ret;
  }

  private function setBools(&$values)
  {
    foreach ($this->usrarg as $short => $long)
    {
      $found = false;
      if (strlen($short) == 1) {
        foreach ($values as $arg => $val)
        {
          if (preg_match("#^$short$#", $arg)) {
            $found = true; 
            break;
          }
        }
        if (!$found) $values[$short] = false;
      }
    }
  }

  private function getValues(array &$error, array &$values)
  {
    $not_found_arg = [];
    $val;
    $del = "NE";

    foreach ($this->cliarg as $arg)
    {
      $arg_found = false;
      foreach ($this->usrarg as $usrShort => $usrLong)
      {
        if (preg_match("#\+.$#", $usrShort)) $del = preg_replace("#^.\+#", "", $usrShort);
        $usrLong = ($usrLong === "#") ? "": "|(-$usrLong)";
        $eq = !preg_match("#:|(\+.)$#", $usrShort) ? "$": "=.+";
        $usrShort = substr($usrShort, 0, 1);

        if (preg_match("#^-($usrShort$usrLong)$eq#", $arg)) {
          $arg_found = true;
          if (strpos($arg, "=")) {
            $val = preg_replace("#^[^=]+=#", "", $arg);
            if ($del !== "NE") $val = explode($del, $val);
          }else {
            $val = true;
          }
          $values[$usrShort] = $val;
          break;
        }
      }
      if ($arg_found === false) $not_found_arg[] = $arg;
    }
    if (isset($not_found_arg[0])) {
      $error = $not_found_arg;
      return ARG_ERROR;
    }
    return true;
  }

  private function printUserError()
  {
    $e = false;

    foreach ($this->usrarg as $shortArg => $longArg)
    {
      if (!preg_match("#^[a-zA-Z](:|(\+.))?$#", $shortArg)) {
        $e = true;
        echo "\e[7;31mCaractère(s) interdit(s) argument court : '$shortArg'\n\e[0m";
      }
      if (!preg_match("#^(\#|[a-zA-Z]+)$#", $longArg))
      {
        $e = true;
        echo "\e[7;31mCaractère(s) interdit(s) argument long : '$longArg'\n\e[0m";
      }
    }
    if ($e) return USR_ERROR; 
    return true;
  }
}
