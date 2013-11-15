<?php

namespace PHPCrypt;

class Autoloader
{
   public static function load($class_name)
   {
      if (class_exists($class_name)) {
         return TRUE;
      }

      $file_name = self::convertClassToPath($class_name);

      $include_paths = explode(PATH_SEPARATOR, get_include_path());

      foreach ($include_paths as $include_path) {

          $full_path = $include_path . DIRECTORY_SEPARATOR . $file_name;

         if (file_exists($full_path)) {
            include_once $file_name;
            return TRUE;
         }
      }
      
      return FALSE;
   }

   public static function install()
   {
       spl_autoload_register('self::load');
   }

   public static function convertClassToPath($class_name)
   {
      $path = str_replace('_', DIRECTORY_SEPARATOR, $class_name);
      $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
      $path .= '.php';

      return $path;
   }
   
   public static function registerIncludePath()
   {
       set_include_path(
           get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__) . '/..') 
       );
   }
}
