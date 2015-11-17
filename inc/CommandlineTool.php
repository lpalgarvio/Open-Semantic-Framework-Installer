<?php

  class CommandlineTool
  {
    /* Specify if if we output everything we got from the commands */
    protected $verbose = FALSE;

    /* Full path of the logfile */
    protected $log_file = '';

    /* Specify that the installation process occurs in an automatic deployment framework */
    protected $auto_deploy = TRUE;

    /* Current working directory */
    protected $currentWorkingDirectory;

    function __construct()
    {
      $this->currentWorkingDirectory = getcwd();
    }

    /**
     * Enable the verbosity of the class. Everything get outputed to the 
     * shell terminal
     */
    public function verbose()
    {
      $this->verbose = TRUE;
    }

    /**
     * Disable the verbosity of the class. No command output will be displayed
     * to the terminal.
     */
    public function silent()
    {
      $this->verbose = FALSE;
    }

    /**
     * Colorize an output to the shell terminal.
     * 
     * @param mixed $text    Text to echo into the terminal screen
     * @param mixed $color   Color to use
     * @param mixed $return  specify if we want to return the colorized text to
     *                       the script instead of the terminal
     */
    public function cecho($text, $color = "NORMAL", $return = FALSE)
    {
      // Log the text
      file_put_contents($this->log_file, $text, FILE_APPEND);

      $_colors = array(
        'LIGHT_RED'    => "[1;31m",
        'LIGHT_GREEN'  => "[1;32m",
        'YELLOW'       => "[1;33m",
        'LIGHT_BLUE'   => "[1;34m",
        'MAGENTA'      => "[1;35m",
        'LIGHT_CYAN'   => "[1;36m",
        'WHITE'        => "[1;37m",
        'NORMAL'       => "[0m",
        'BLACK'        => "[0;30m",
        'RED'          => "[0;31m",
        'GREEN'        => "[0;32m",
        'BROWN'        => "[0;33m",
        'BLUE'         => "[0;34m",
        'CYAN'         => "[0;36m",
        'BOLD'         => "[1m",
        'UNDERSCORE'   => "[4m",
        'REVERSE'      => "[7m",
      );

      $out = $_colors["$color"];
      if ($out == "") {
        $out = "[0m";
      }

      if ($return) {
        return(chr(27) . "$out$text" . chr(27) . "[0m");
      } else {
        echo chr(27) . "$out$text" . chr(27) . chr(27) . "[0m";
      }
    }

    /**
     * Outputs a header #1 (h1) message.
     * 
     * @param string  $message   Message to output.
     */
    public function h1($message)
    {
      $color = 'WHITE';
      $msglen = strlen($message) + 4;
      $this->cecho("\n", $color);
      $this->cecho(str_repeat('-', $msglen) . "\n", $color);
      $this->cecho("| {$message} |\n", $color);
      $this->cecho(str_repeat('-', $msglen) . "\n", $color);
      $this->cecho("\n", $color);
    }

    /**
     * Outputs a header #2 (h2) message.
     * 
     * @param string  $message   Message to output.
     */
    public function h2($message)
    {
      $color = 'CYAN';
      $this->cecho("\n", $color);
      $this->cecho("{$message}\n", $color);
      $this->cecho("\n", $color);
    }

    /**
     * Outputs a header #3 (h3) message.
     * 
     * @param string  $message   Message to output.
     */
    public function h3($message)
    {
      $color = 'BROWN';
      $this->cecho("\n", $color);
      $this->cecho("{$message}\n", $color);
      $this->cecho("\n", $color);
    }

    /**
     * Outputs a span message.
     * 
     * @param string  $message   Message to output.
     * @param string  $severity  Severity of message (optional).
     */
    public function span($message, $severity = 'info')
    {
      // Check severity
      switch ($severity) {
        case 'info':
          $color = 'WHITE';
          break;
        case 'notice':
          $color = 'CYAN';
          break;
        case 'debug':
          $color = 'BLUE';
          break;
        case 'warn':
          $color = 'YELLOW';
          break;
        case 'error':
          $color = 'RED';
          break;
        case 'good':
          $color = 'GREEN';
          break;
        default:
          $color = 'WHITE';
          break;
      }
      $this->cecho("{$message}\n", $color);
    }

    /**
     * Log information into the logging file.
     * 
     * @param array    $lines         An array of lines to log into the file.
     * @param boolean  $forceSilence  Overwrite the verbosity of the script
     *                                and make sure that log() stay silent.
     */
    public function log($lines, $forceSilence = FALSE)
    {
      foreach($lines as $line) {
        file_put_contents($this->log_file, $line."\n", FILE_APPEND);
        if($this->verbose && !$forceSilence) {
          $this->span($line, 'debug');
        }
      }
    }

    /**
     * Analyzes ouput of commands.
     *
     * @param mixed    $commandReturnVal  Result of the command, normally as
     *                                    a boolean or integer value.
     * @param integer  $errorStatus       Error status code.
     * @param string   $errorLevel        Error level mode.
     *
     * There are 4 levels:
     * (1) ignore, (2) notice, (3) warning and (4) error.
     * The ignore level doesn't display anything, the notice level outputs an
     * error in light-cyan color, the warning level outputs an error message in
     * yellow color, and a error error outputs an error message in red color
     * and stops the execution of the script.
     */
    private function commandReturn($commandReturnVal, $errorStatus, $errorLevel = 'error')
    {
      // Check for boolean result
      if(is_bool($commandReturnVal)) {
        if($commandReturnVal === FALSE) {
          $commandReturnVal = 1;
        } else {
          $commandReturnVal = 0;
        }
      }

      // Parse the error
      if ($commandReturnVal > 0) {
        // Respect the errorLevel imposed
        switch (strtolower($errorLevel)) {

          // Ignore errors
          case 'ignore':
            return(TRUE);
          break;

          // Notices of low importance
          case 'notice':
            $this->span("An occured but the script continue its process. Check the log to see what was the error: {$this->log_file}", 'notice');
          break;

          // Warnings without major impact
          case 'warning':
            $this->span("An occured but the script continue its process. Check the log to see what was the error: {$this->log_file}", 'warn');
          break;

          // Errors with severe consequences
          case 'error':
            if($this->auto_deploy == FALSE) {
              $this->span("A non-recoverable error happened. Check the log to see what was the error: {$this->log_file}", 'error');
              // Get answer
              $continue = $this->isYes($this->getInput("Do you want to continue the execution. If yes, then try to fix this error by hands before continuing, otherwise errors may occurs later in the process? (yes/no)\n"));
              if ($continue == FALSE) {
                exit($errorStatus);
              }
            }
            else {
              exit($errorStatus);
            }
          break;

        }
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Prompt the user with a question, wait for input, and return that input
     * from the user.
     *     
     * @param  mixed    $msg  Message to display to the user before waiting
     *                        for an answer.
     * 
     * @return boolean  Returns the answer of the user.
     */
    public function getInput($msg)
    {
      // Prompt and get an answer
      fwrite(STDOUT, $this->cecho("$msg: ", 'MAGENTA', TRUE));
      $input = trim(fgets(STDIN));

      // Log the input
      $this->log(array("[USER-INPUT]: {$input}\n"), TRUE);

      return $input;
    }

    /**
     * Check if the answer of an input is equivalent to "yes".
     * The strings that are equivalent to "yes" are:
     *   "1", "true", "on", "y" and "yes".
     * The strings that are equivalent to "no" are:
     *   "0", "false", "off", "n" and "no".
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is equivalent to "yes",
     *                  FALSE otherwise.
     */
    public function isYes($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Get a boolean with filter
      $options = array(
        'flags' => FILTER_NULL_ON_FAILURE,
      );
      $result = filter_var(strtolower($input), FILTER_VALIDATE_BOOLEAN, $options);

      if ($result == FALSE || $input == 'n') {
        return(FALSE);
      } elseif ($result == TRUE || $input == 'y') {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a boolean.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid boolean,
     *                  FALSE otherwise.
     */
    public function isBoolean($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with filter
      $options = array(
        'flags' => FILTER_NULL_ON_FAILURE,
      );
      $validation = filter_var($input, FILTER_VALIDATE_BOOLEAN, $options);

      if (is_null($validation)) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Get a boolean value from an input.
     * 
     * @param  mixed    $input  Input to parse.
     * 
     * @return boolean  Returns always TRUE or FALSE.
     */
    public function getBoolean($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Get a boolean with filter
      $options = array(
        'flags' => FILTER_NULL_ON_FAILURE,
      );
      $result = (filter_var($input, FILTER_VALIDATE_BOOLEAN, $options)) ? 'true' : 'false';

      return($result);
    }

    /**
     * Check if the provided input is a integer number.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid integer number,
     *                  FALSE otherwise.
     */
    public function isInteger($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with filter
      $validation = filter_var($input, FILTER_VALIDATE_INT);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is an alpha numeric string.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid alpha numeric,
     *                  FALSE otherwise.
     */
    public function isAlphaNumeric($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      // https://stackoverflow.com/questions/336210/regular-expression-for-alphanumeric-and-underscores
      $validation = preg_match('/^[a-zA-Z0-9]*$/', $input);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a valid version.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid version,
     *                  FALSE otherwise.
     */
    public function isVersion($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      $validation = preg_match('/^(0|[1-9]\d*)(\.(0|[1-9]\d*)){0,2}(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/', $input);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a valid path.
     * 
     * @param  mixed    $input     Input to test.
     * @param  mixed    $absolute  Treat the path as absolute or not.
     * 
     * @return boolean  Returns TRUE if the input is a valid path,
     *                  FALSE otherwise.
     */
    public function isPath($input, $absolute = TRUE)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      if ($absolute = TRUE) {
        $validation = preg_match('#^(/[^/]+)+$#', rtrim($input, '/'));
      } else {
        $validation = preg_match('^[a-z0-9]([a-z0-9-]*[a-z0-9])?(/[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$^', rtrim($input, '/'));
      }

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Get a path from an input.
     * 
     * @param  mixed    $input  Input to parse.
     * 
     * @return string   Returns a string with the path.
     */
    public function getPath($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Trim excessive slashes
      $result = rtrim($input, '/');

      return($result);
    }

    /**
     * Check if the provided input is a valid domain.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid domain,
     *                  FALSE otherwise.
     */
    public function isDomain($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      // https://stackoverflow.com/questions/3026957/how-to-validate-a-domain-name-using-regex-php
      $validation = preg_match('^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$^', $input);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a valid IP address.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid IP address,
     *                  FALSE otherwise.
     */
    public function isIP($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with filter
      $validation = filter_var($input, FILTER_VALIDATE_IP);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a port number.
     * 
     * @param  mixed    $input  Input to test.
     * 
     * @return boolean  Returns TRUE if the input is a valid port number,
     *                  FALSE otherwise.
     */
    public function isPort($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with filter
      $options = array(
        'options' => array(
          'min_range' => 1,
          'max_range' => 65535,
        ),
      );
      $validation = filter_var($input, FILTER_VALIDATE_INT, $options);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Execute a shell command.
     *     
     * @param  string   $command     Shell command to execute.
     * @param  string   $errorLevel  Level of the error if an error happens.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function exec($command, $errorLevel = 'error')
    {
      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 2, $errorLevel));
    }

    /**
     * Change the current folder of the script.
     *     
     * @param  string   $dir         Folder path where to go.
     * @param  string   $errorLevel  Level of the error if an error happens.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function chdir($dir, $errorLevel = 'error')
    {
      $this->log(array("cd {$dir}"), TRUE);

      // Execute command
      $return = chdir($dir);

      return($this->commandReturn($return, 3, $errorLevel));
    }

    /**
     * Append data to a file.
     * 
     * @param  string   $data  Data to append.
     * @param  string   $file  File to update.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function append($data, $file)
    {
      $this->log(array("Append: ", $data, $file), TRUE);

      // Execute command
      $return = file_put_contents($file, $data, FILE_APPEND);

      return($this->commandReturn(($return === FALSE ? 5 : 0), 5));
    }

    /**
     * Finds and replaces content in a file, using sed.
     * 
     * @param  string   $find       String to find.
     * @param  string   $replace    String to replace.
     * @param  string   $file       File to update.
     * @param  string   $modifiers  Modifiers to apply.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function sed($find, $replace, $file, $modifiers = '')
    {
      // Escape reserved sed characters
      $find = str_replace(array('"', '$', '>'), array('\"', '\$', '\>'), $find);
      $replace = str_replace(array('"', '$', '>'), array('\"', '\$', '\>'), $replace);

      // Build command
      $command = "sed -i \"s>{$find}>{$replace}>{$modifiers}\" \"{$file}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 4));
    }

    /**
     * Sets an option in a ini file, using sed.
     * 
     * @param  string   $section  Section to find.
     * @param  string   $option   Option to change.
     * @param  string   $value    Value to set.
     * @param  string   $file     File to update.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function setIni($section, $option, $value, $file)
    {
      // Build command
      // https://stackoverflow.com/questions/10040255/edit-file-in-unix-using-sed
      // sed -ie '/^\[Section B\]/,/^\[.*\]/s/^\(\$param2[ \t]*=[ \t]*\).*$/\1new_value/' foo.txt
      $value = str_replace("/", "\/", $value);
      $command = "sed -i -e '/^\[{$section}\]/,/^\[.*\]/s/^\({$option}[ \\t]*=[ \\t]*\).*$/\\1{$value}/' \"{$file}\"";

      // Log command
      $this->log(array($section, $option, $value, $file), TRUE);
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 6));
    }

    /**
     * Changes owner for a path.
     * 
     * @param  string   $path       Path to target.
     * @param  string   $own        Owner to apply.
     * @param  boolean  $recursion  Enable or disable recursion (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function chown($path, $own, $recursion = FALSE)
    {
      // Build command
      $command = "chown";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Append owner and path
      $command .= " \"{$own}\" \"{$path}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 9));
    }

    /**
     * Changes group for a path.
     * 
     * @param  string   $path       Path to target.
     * @param  string   $grp        Group to apply.
     * @param  boolean  $recursion  Enable or disable recursion (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function chgrp($path, $grp, $recursion = FALSE)
    {
      // Build command
      $command = "chgrp";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Append group and path
      $command .= " \"{$grp}\" \"{$path}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 10));
    }

    /**
     * Changes permissions for a path
     * 
     * @param  string   $path       Path to target.
     * @param  string   $mod        Permissions modifier in octal
     *                              or symbolic notion.
     * @param  boolean  $recursion  Enable or disable recursion (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function chmod($path, $mod, $recursion = FALSE)
    {
      // Build command
      $command = "chmod";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Append mod and path
      $command .= " \"{$mod}\" \"{$path}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 11));
    }

    /**
     * Creates a directory.
     * 
     * @param  string   $path  Path to create.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function mkdir($path)
    {
      // Build command
      $command = "mkdir -p \"{$path}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 7));
    }

    /**
     * Soft links a source file or directory.
     * 
     * @param  string   $src   Source file or directory.
     * @param  string   $dest  Destination file or directory (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function ln($src, $dest = '')
    {
      // Build command
      $command = "ln -sf \"{$src}\"";
      // Check for destination
      if (!empty($dest)) {
        $command .= " \"{$dest}\"";
      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 12));
    }

    /**
     * Copies source files or directories to destination.
     * 
     * @param  string   $src        Source file or directory.
     * @param  string   $dest       Destination file or directory.
     * @param  boolean  $recursion  Enable or disable recursion (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function cp($src, $dest, $recursion = FALSE)
    {
      // Build command
      $command = "cp -af";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Check for asterisk
      if(strpos($src, '*') !== FALSE) {
        $command .= " {$src} \"{$dest}\"";
      } else {     
        $command .= " \"{$src}\" \"{$dest}\"";
      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 13));
    }

    /**
     * Moves source files or directories to destination.
     * 
     * @param  string   $src   Source file or directory.
     * @param  string   $dest  Destination file or directory.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function mv($src, $dest)
    {
      // Build command
      $command = '';
      // Check for asterisk
      if($src == '*') {
        $command = "mv -f * \"{$dest}\"";
      } else {
        $command = "mv -f \"{$src}\" \"{$dest}\"";
      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 14));
    }

    /**
     * Remove path from filesystem.
     * 
     * @param  string   $path       Path to remove.
     * @param  boolean  $recursion  Enable or disable recursion (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function rm($path, $recursion = FALSE)
    {
      // Build command
      $command = "rm -f";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Check for asterisk
      if(strpos($path, '*') !== FALSE) {
        $command .= " {$path}";
      } else {
        $command .= " \"{$path}\"";
      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 8));
    }

    /**
     * Unzips an archive in the ZIP format, using unzip command.
     * 
     * @param  string   $arch  Archive file.
     * @param  string   $dest  Destination directory (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function unzip($arch, $dest = '')
    {
      // Build command
      $command = "unzip -o \"{$arch}\"";
      // Check for destination
      if (!empty($dest)) {
        $command .= " -d \"{$dest}\"";
      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 15));
    }

    /**
     * Downloads an URL to local system, using wget command.
     * 
     * @param  string   $url    Source URL.
     * @param  string   $dest   Destination directory (optional).
     * @param  boolean  $retry  Retry the download (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function wget($url, $dest = '', $retry = TRUE)
    {
      // Build command
      $command = "wget -q --timestamping";
      // Check for retry
      if ($retry == TRUE) {
        $command .= " --tries=3";
      }
      // Check for destination
      if (!empty($dest)) {
        $command .= " --directory-prefix=\"{$dest}\"";
      }
      // Append URL
      $command .= " \"{$url}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 16));
    }

    /**
     * Downloads an URL to local system, using curl command.
     * 
     * @param  string   $url    Source URL.
     * @param  string   $dest   Destination file (optional).
     * @param  boolean  $retry  Retry the download (optional).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function curl($url, $dest = '', $retry = TRUE)
    {
      // Build command
      $command = "curl -s";
      // Check for retry
      if ($retry == TRUE) {
        $command .= " --retry 3";
      }
      // Check for destination
      if (!empty($dest)) {
        $command .= " --output \"{$dest}\"";
      }
      // Append URL
      $command .= " \"{$url}\"";

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 17));
    }

    /**
     * Applies an action to the Package Management System (PMS).
     * 
     * @param  string   $action    Action to take on the PMS.
     *                             Allowed actions: refresh (caches),
     *                             clear (caches), and upgrade (packages).
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function pmsAction($action)
    {
      // Prepare commands for each distro PMS
      switch($this->distro_pms) {

        // Apt PMS
        case 'apt':
          // Refresh repo caches
          if($action == 'refresh') {
            // Build command
            $command = "apt-get update";
          // Clear repo caches
          } elseif($action == 'clear') {
            // Build command
            $command = "apt-get clean";
          // Upgrade packages
          } elseif($action == 'upgrade') {
            // Build command
            $command = "apt-get upgrade -y";
          }
          break;

        // YUM PMS
        case 'yum':
          // Refresh repo caches
          if($action == 'refresh') {
            // Build command
            $command = "yum makecache";
          // Clear repo caches
          } elseif($action == 'clear') {
            // Build command
            $command = "yum clean";
          // Upgrade packages
          } elseif($action == 'upgrade') {
            // Build command
            $command = "yum update -y";
          }
          break;

        // Unknown PMS
        default:
          //TODO FAIL
          break;

      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 18));
    }

    /**
     * Applies an action to a given array of package(s), using the 
     * Package Management System (PMS).
     * 
     * @param  string   $action    Action to take on the package(s).
     *                             Allowed actions: install, reinstall,
     *                             and uninstall.
     * @param  array    $packages  Package(s) to affect.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function packageAction($action, $packages)
    {
      // Flat the list of packages
      $package_list = '';
      foreach ($packages as &$package) {
        $package_list .= $package;
      }

      // Prepare commands for each distro PMS
      switch($this->distro_pms) {

        // Apt PMS
        case 'apt':
          // Install package(s)
          if($action == 'install') {
            // Build command
            $command = "apt-get install -y {$package_list}";
          // Reinstall package(s)
          } elseif($action == 'reinstall') {
            // Build command
            $command = "apt-get install --reinstall -y {$package_list}";
          // Uninstall package(s)
          } elseif($action == 'uninstall') {
            // Build command
            $command = "apt-get uninstall --purge -y {$package_list}";
          }
          break;

        // YUM PMS
        case 'yum':
          // Install package(s)
          if($action == 'install') {
            // Build command
            $command = "yum install -y {$package_list}";
          // Reinstall package(s)
          } elseif($action == 'reinstall') {
            // Build command
            $command = "yum reinstall -y {$package_list}";
          // Uninstall package(s)
          } elseif($action == 'uninstall') {
            // Build command
            $command = "yum remove -y {$package_list}";
          }
          break;

        // Unknown PMS
        default:
          //TODO FAIL
          break;

      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 19));
    }

    /**
     * Configures the service in the system.
     * 
     * @param  string   $action    Action to take on the service.
     *                             Allowed actions: enable, disable,
     *                             status, start, restart, stop.
     * @param  array    $service   Package(s) to affect.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function serviceConfig($action, $service)
    {
      // Prepare commands for each distro srvconf
      switch($this->distro_srvconf) {

        // update-rc.d srvconf
        case 'update-rc.d':
          // Enable service
          if($action == 'enable') {
            // Build command
            $command = "update-rc.d {$service} enable";
          // Disable service
          } elseif($action == 'disable') {
            // Build command
            $command = "update-rc.d {$service} disable";
          }
          break;

        // chkconfig srvconf
        case 'chkconfig':
          // Enable service
          if($action == 'enable') {
            // Build command
            $command = "chkconfig {$service} on";
          // Disable service
          } elseif($action == 'disable') {
            // Build command
            $command = "chkconfig {$service} off";
          }
          break;

        // Systemd srvconf
        case 'systemd':
          // Enable service
          if($action == 'enable') {
            // Build command
            $command = "systemctl enable {$service}";
          // Disable service
          } elseif($action == 'disable') {
            // Build command
            $command = "systemctl disable {$service}";
          }
          break;

        // Unknown srvconf
        default:
          //TODO FAIL
          break;

      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 20));
    }

    /**
     * Applies an action to a given service in the system.
     * 
     * @param  string   $action    Action to take on the service.
     *                             Allowed actions: status, reload,
     *                             start, restart and stop.
     * @param  array    $service   Package(s) to affect.
     *
     * @return boolean  Returns the result from commandReturn().
     */
    public function serviceAction($action, $service)
    {
      // Prepare commands for each distro Initd
      switch($this->distro_initd) {

        // SysVinit Initd
        case 'sysvinit':
          // Check status of service
          if($action == 'status') {
            // Build command
            $command = "service {$service} status";
          // Reload service
          } elseif($action == 'reload') {
            // Build command
            $command = "service {$service} reload";
          // Start service
          } elseif($action == 'start') {
            // Build command
            $command = "service {$service} start";
          // Restart service
          } elseif($action == 'restart') {
            // Build command
            $command = "service {$service} restart";
          // Stop service
          } elseif($action == 'stop') {
            // Build command
            $command = "service {$service} stop";
          }
          break;

        // Systemd Initd
        case 'systemd':
          // Check status of service
          if($action == 'status') {
            // Build command
            $command = "systemctl status {$service}";
          // Reload service
          } elseif($action == 'reload') {
            // Build command
            $command = "systemctl reload {$service}";
          // Start service
          } elseif($action == 'start') {
            // Build command
            $command = "systemctl start {$service}";
          // Restart service
          } elseif($action == 'restart') {
            // Build command
            $command = "systemctl restart {$service}";
          // Stop service
          } elseif($action == 'stop') {
            // Build command
            $command = "systemctl stop {$service}";
          }
          break;

        // Unknown Initd
        default:
          // Check status of service
          if($action == 'status') {
            // Build command
            $command = "/etc/init.d/{$service} status";
          // Reload service
          } elseif($action == 'reload') {
            // Build command
            $command = "/etc/init.d/{$service} reload";
          // Start service
          } elseif($action == 'start') {
            // Build command
            $command = "/etc/init.d/{$service} start";
          // Restart service
          } elseif($action == 'restart') {
            // Build command
            $command = "/etc/init.d/{$service} restart";
          // Stop service
          } elseif($action == 'stop') {
            // Build command
            $command = "/etc/init.d/{$service} stop";
          }
          break;

      }

      // Log command
      $this->log(array($command), TRUE);

      // Execute command and log output
      $output = array();
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 21));
    }

  }

