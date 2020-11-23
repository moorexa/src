<?php
/**
 * @author Amadi Ifeanyi <amadiify.com>, Fregatelab <fregatelab.com>
 * @throws Exception
 * Main installation file. This file will complete the installation for a new moorexa project.
 */

 // files to copy to root directory only
 $filesToRootDirectory = [
    __DIR__ . '/.htaccess',
    __DIR__ . '/index.php',
    __DIR__ . '/test.yaml',
    __DIR__ . '/assist',
 ];

 // @var bool $standardInput
 $standardInput = false;

 // check in args
 if (in_array('--standardInput', $_SERVER['argv'])) $standardInput = true;

 // get the root directory
 $rootDirectory = !isset($rootDirectory) ? __DIR__ . '/../' : $rootDirectory;

 // now copy files and delete them from current directory
 array_map(function($fileName) use (&$rootDirectory) {

    // check if root directory exists
    if (!is_dir($rootDirectory) || !file_exists($fileName)) return false;

    // get base name
    $baseName = basename($fileName);

    // try copy file now
    copy($fileName, $rootDirectory . $baseName);

    // delete file from current directory
    if (file_exists($rootDirectory . $baseName)) unlink($fileName);

    // ok all good
    return true;

 }, $filesToRootDirectory);

 // get the project type
 $projectPackageTypes = [
     'micro'    => 'Lightroom\Packager\Moorexa\MoorexaMicroPackager::class',
     'default'  => 'Lightroom\Packager\Moorexa\MoorexaWebPackager::class'
 ];

 // get the init file
 if (!file_exists(__DIR__ . '/init.php')) throw new Exception('Init File could not be found. Installation could not complete'); 

 // get the content body
 $initContent = file_get_contents(__DIR__ . '/init.php');

 // add the default content type
 $contentType = 'text/html';

 // read composer.json
 if (file_exists($rootDirectory . '/composer.json')) :

   // read json file
   $jsonFile = json_decode(file_get_contents($rootDirectory . '/composer.json'));

   // get name
   if (strpos($jsonFile->name, 'micro') > 0) :

      // set the project type
      $projectType = 'micro';

      // set the default content type
      $contentType = 'application/json';
      
   endif;

 endif;

 // get the project type
 $projectType = !isset($projectType) ? 'default' : $projectType;

 // check for project type. 
 if (!isset($projectPackageTypes[$projectType])) throw new Exception('Project Type: ' . $projectType . ' does not exists.');

 // create variable
 $initContent .= "\n\n// default packager\n" . '$MAIN_PACKAGER = ' . ($projectPackageTypes[$projectType]) . ';';

 // add the default time zone
 $defaultTimeZone = 'Africa/Lagos';

 // create read input function
 $readInput = function(string $question, string $default)
 {
   // show 
   fwrite(STDOUT, $question . ': ');
   
   // get input
   $input = trim(fgets(STDIN));

   // return input
   return ($input == '') ? $default : $input;
 };

 // not standard input
 if ($standardInput === false) :

    // ask for content type
    $contentType = $readInput('Please enter a default content type. (Tap Enter to use default "'.$contentType.'")', $contentType);

    // ask for default time zone
    $defaultTimeZone = $readInput('Please enter a default time zone. (Tap Enter to use default "'.$defaultTimeZone.'")', $defaultTimeZone);

    // create constant for content type
    $initContent .= "\n\n// default content type\n" . 'define(\'DEFAULT_CONTENT_TYPE\', \''.$contentType.'\');';

    // create constant for timezone
    $initContent .= "\n\n// default timezone\n" . 'define(\'DEFAULT_TIME_ZONE\', \''.$defaultTimeZone.'\');';

    // get default controller 
    $defaultController = $readInput('Please enter a default controller name. (Tap Enter to use default "@starter")', '@starter');

    // get default view
    $defaultView = $readInput('Please enter a default view name. (Tap Enter to use default "home")', 'home');

    // create file in the root directory
    file_put_contents($rootDirectory . 'init.php', $initContent);

    // delete file
    if (file_exists($rootDirectory . 'init.php')) :

        // remove the init file
        unlink(__DIR__ . '/init.php');

        // update config for micro project
        if ($projectType == 'micro') :

          // set micro config body
          $microConfig  = '# Beautiful url' . "\n";
          $microConfig .= 'beautiful_url_target : __app_request__' . "\n";
          $microConfig .= '# Router global configuration' . "\n";
          $microConfig .= 'router :' . "\n";
          $microConfig .= ' # set default controller and view ' . "\n";
          $microConfig .= ' default : ' . "\n";
          $microConfig .= '  controller : \''.$defaultController.'\'' . "\n";
          $microConfig .= '  view : \''.$defaultView.'\'' . "\n";
          $microConfig .= '# actions for model' . "\n";
          $microConfig .= 'actions : ' . "\n";
          $microConfig .= " - add\n - create\n - edit\n - delete\n - show";

          // update config.yaml file
          if (file_exists(__DIR__ . '/config.yaml')) file_put_contents(__DIR__ . '/config.yaml', $microConfig);

        endif;

        // update config for default project
        if ($projectType == 'default' && file_exists(__DIR__ . '/config.yaml')) :

          // read config
          $configContent = file_get_contents(__DIR__ . '/config.yaml');

          // update controller
          $configContent = str_replace('@starter', $defaultController, $configContent);

          // update view
          $configContent = str_replace("'home'", $defaultView, $configContent);

          // save now
          file_put_contents(__DIR__ . '/config.yaml', $configContent);

        endif;

        // remove installer file
        unlink(__DIR__ . '/install.php');

        // installation complete
        fwrite(STDOUT, 'Installation complete.' . PHP_EOL);

    endif;

// end
else:

  // ok save response
  file_put_contents(__DIR__ . '/installResponse.txt', json_encode([
    'initContent'     => $initContent,
    'defaultTimeZone' => $defaultTimeZone,
    'projectType'     => $projectType,
    'contentType'     => $contentType
  ], JSON_PRETTY_PRINT));

  // remove installer file
  unlink(__DIR__ . '/install.php');

endif;
