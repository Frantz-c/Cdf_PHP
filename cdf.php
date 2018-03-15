#!/usr/bin/php
<?php

/*
** "cdf.php"
**
** Ouvre tous les fichiers dans tous les sous dossiers (niveau de récursivité réglable)
** et recherche un masque à l'intérieur.
** 
** Renvoie le nom du ou des fichier(s) ainsi que le N° de la ou des ligne(s) correspondante(s)
** 
** cdf -e="regex" [-d="dossier"]? [-n="niveau récursivité"]? [-a]? [-v]?
** cdf "regex" (-v auto && -r=5)
*/

include "cliarg.php";


$nb_fichier = [0, 0];
$script_name = array_shift($argv);

if ($argc == 1) {

  PrintHelp();
  return 0;
}
else if ($argc >= 2) {

  $exp   = array_shift($argv);
  $argc -= 2;

  if (($func_arg = GetArguments($argv, $argc, $exp)) == NULL) {
    return -1;
  }

  echo PHP_EOL . "\e[1;35mRegex = '" . $func_arg['exp'] . "'" . PHP_EOL;
  if ($func_arg['fil'] != NULL) {
    $i = 0;
    foreach ($func_arg['fil'] as $filename) {
      echo 'file[' . ($i++) . '] = ' . $filename . PHP_EOL;
    }
  }
  echo "\e[0m" . PHP_EOL;

  $cdf = new Cdf($func_arg);
  $cdf->Search();
  return 0;
}

function PrintHelp()
{
  echo "\e[7;31m                             Mode d'emploi :                                \e[0m" . PHP_EOL . PHP_EOL
    . "\e[1;31mcdf -e\e[0m=\e[0;33m\"regex\"\e[0m"
    . " [\e[1;31m-d\e[0m=\e[0;33m\"repertoire\"\e[0m]\e[1;32m?\e[0m"
    . " [\e[1;31m-r\e[0m=\e[0;33m\"recursivité\"\e[0m]\e[1;32m?\e[0m"
    . " [\e[1;31m-f\e[0m=\e[0;33m\"files\"\e[0m]\e[1;32m?\e[0m"
    . " [\e[1;31m-a\e[0m]\e[1;32m?\e[0m"
    . " [\e[1;31m-v\e[0m]\e[1;32m?" . PHP_EOL . PHP_EOL
    . "\e[0;32mArguments : " . PHP_EOL
    . "  \e[0;36m-d / --directory : \e[0;37mDossier racine de la recherche \e[0m(\e[1;34mstring\e[0m)" . PHP_EOL
    . "  \e[0;36m-f / --files     : \e[0;37mFichiers dans lesquels chercher \e[0m(\e[1;34mstring(,string)*\e[0m)" . PHP_EOL
    . "  \e[0;36m-r / --recursive : \e[0;37mNiveau de récursivité \e[0m(\e[1;34minteger\e[0m)" . PHP_EOL
    . "  \e[0;36m-a / --all       : \e[0;37mRechercher dans les dossiers & les fichiers cachés \e[0m(\e[1;34mboolean\e[0m)" . PHP_EOL . PHP_EOL;
}

function GetArguments($argv, $argc, $exp)
{
  $error = [];
  $arguments = [
    'd:' => 'directory',
    'r:' => 'recursive',
    'a'  => 'all',
    'f:' => 'files'
  ];
  $func_arg = [
    'dir' => getcwd() . DIRECTORY_SEPARATOR, 
    'rec' => 1, 
    'exp' => $exp, 
    'all' => false,
    'fil' => NULL
  ];
  $cliarg = new Cliarg($arguments, $argv);
  $args = $cliarg->check($error);


  // S'il y a au moins une erreur, on affiche le tableau $error
  if ($args === ARG_ERROR) {
    echo "\e[1m\e[7;31m";
    foreach ($error as $e) echo "Argument '$e' incorrect !\n";
    echo "\e[0m";
    return -2;
  }
  else if ($args === USR_ERROR) {
    return -3;
  }

  // Si tout va bien, on assigne les valeurs des arguments au tableau $func_arg
  if ($argc < 5) 
  {
    if (isset($args['d'])) {
      $func_arg['dir'] = $args['d'];
    }

    if (isset($args['r']))
    {
      if (is_numeric($args['r'])) {
        $func_arg['rec'] = (int)$args['r'];
      }
      else {
        echo "\e[1m\e[7;31mErreur : la valeur de -r (ou --recurive) doit être un nombre !\n\e[0m";
        return -5;
      }
    }

    if (isset($args['f'])) $func_arg['fil'] = (strpos($args['f'], ',')) ? 
      explode(',', $args['f']): $args['f'];

    return $func_arg;
  }
  return NULL;
}

class Cdf
{
  private $arg;
  //private $result = "";
  private $nb_match = 0;
  private $nb_files = 0;

  public function __construct($arg)
  {
    $this->arg = $arg;
  }

  public function Search()
  {
    $txt = " correspondance";
    $txt2 = " fichier";
    
    //$this->result = PHP_EOL;
    $this->Recursive(preg_replace("#" . DIRECTORY_SEPARATOR . "$#", "", $this->arg['dir']));

    if ($this->nb_match > 1 || $this->nb_match == 0)
      $txt .= 's dans ';
    else
      $txt .= ' dans ';

    if ($this->nb_files > 1 || $this->nb_files == 0)
      $txt2 .= 's !' . PHP_EOL;
    else
      $txt2 .= ' !' . PHP_EOL;

    echo PHP_EOL . "\e[1;34m" . $this->nb_match . $txt . $this->nb_files . $txt2 . "\e[0m";
  }

  private function ReadFileContent($file, $exp)
  {
    $first_time = true;
    $match = "";

    if (($content = @fopen($file, "r")) !== false)
    {
      $count = 1;

      while (($line = fgets($content)) !== false)
      {
        if (preg_match("#". $exp . "#", $line, $m)) {
          if ($first_time) {
            $this->nb_files++;
            $match .= "\e[1m\e[7;34m.» $file «.\e[0m" . PHP_EOL
              . "\e[0;37mLigne(s) \e[1;31m" . PHP_EOL;
          }
/*          
          if ($this->arg['ver'] === false) 
          {
            if ($first_time) {
              $match .= $count;
              $first_time = false;
            }else {
              $match .= ", $count";
            }
          }
          else 
          {*/
            $line = "\e[0;37m$count : \e[0m" . str_replace($m[0], "\e[1;32m" . $m[0] . "\e[0m", $line);
            $match .= $line;
            if ($first_time) $first_time = false;
          //}
          $this->nb_match++;
        }
        $count++;
      }
      if (!$first_time) echo $match . "\e[0m" . PHP_EOL;
      fclose($content);
    }
  }

  private function Recursive($path, $lvl = 1)
  {
    if ($lvl > $this->arg['rec']) return;

    $file = "";
    if (($dir = @opendir($path)) !== false)
    {
      while (($file = readdir($dir))) 
      {
        if ($this->arg['all'] || substr($file, 0, 1) !== ".") {
          if ($file !== ".." && $file !== ".") {
            if ($this->SearchOnThisFile($file))
            {
              $file = $path . DIRECTORY_SEPARATOR . $file;
              if (is_dir($file) !== false) {
                $this->Recursive($file, $lvl + 1);
              }
              else if (file_exists($file))
              {
                
                if ($this->arg['fil'] || $this->Valid_ext($file)) {
                  $this->ReadFileContent($file, $this->arg['exp']);
                }
              }
            }
          }
        }
      }
      closedir($dir);
    }
  }

  private function SearchOnThisFile($file)
  {
    if ($this->arg['fil'] === NULL) return true;

    if (is_string($this->arg['fil'])) {
      return ($file === $this->arg['fil']);
    }

    foreach ($this->arg['fil'] as $file_ok) {
      if ($file === $file_ok) return true;
    }
    return false;
  }

  private function Valid_ext(string $file)
  {
    if (is_executable($file) && !preg_match("#\..*$#", $file)) return false;

    preg_match("#\.[a-zA-Z\d]+$#", $file, $ext);

    if (isset($ext[0]))
    {
      $ext = strtolower($ext[0]);
      switch ($ext) {
        //IMAGE
        case '.jpg': return false;
        case '.jpeg': return false;
        case '.bmp': return false;
        case '.gif': return false;
        case '.png': return false;
        case '.tiff': return false;
        case '.tif': return false;
        //SON
        case '.mp3': return false;
        case '.wma': return false;
        case '.wave': return false;
        case '.riff': return false;
        case '.aiff': return false;
        case '.raw': return false;
        case '.ogg': return false;
        //VIDEO
        case '.mp4': return false;
        case '.avi': return false;
        case '.wmv': return false;
        case '.mov': return false;
        case '.flv': return false;
        case '.mpg': return false;
        case '.rmvb': return false;
        // TEXT
        case '.odt': return false;
        case '.pdf': return false;
        // image disque
        case '.iso': return false;
        // objet, db, ...
        case '.o': return false;
        case '.so': return false;
        case '.db': return false;
      }
    }
    return true;
  }

}

