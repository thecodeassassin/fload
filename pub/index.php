<?php
error_reporting(0);
ini_set('display_errors' , 0);
/*
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * <frits [at] hccniet.nl> wrote this file. As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a beer in return. Frits Vlaanderen
 * ----------------------------------------------------------------------------
 */

 /**********************************\
 *                                  *
 *  FLoad                           *
 *  PHP Filedump script             *
 *                                  *
 *   Stolen parts from php.net      *
 *   Will make it cooler sometime   *
 *                                  *
 *   Required:                      *
 *    - Webserver with X-Sendfile   *
 *    - PHP (duh)                   *
 *    - htaccess (or similar) with  *
 *      mod_rewrite like stuff      *
 *    - mongodb                     *
 *                                  *
 \**********************************/

// TODO: Make more stuff configurable (isn't this just cleanup?)
//
// TODO: Rewrite upload part to checksum each chunk, store the checksums in mongodb.
//        This way we can detect if a similar file already exists before it is fully uploaded.
//        
// TODO: Remove writing to tmpfile, write directly to destination
//
// TODO: Do something with the statistics in stats collection
//
// TODO: rewrite all mongoDB stuff to support $otherDB backends as well
//
// TODO: Remove all apache dependency (Done?)
//        Confirm if I work with $otherWebserver
//        Webserver needs support for chunking!
//
// TODO: CLEANUP!!
//


// Attempt to read the configuration
$configFile = __DIR__ . '/../config.ini';

if (!is_readable($configFile)) {
   die('No config.ini found in root directory');
}

// I use an external file
$config = parse_ini_file($configFile, true);

// This function is a (really) minor modification of Aidan Lister's str_rand()
function str_rand($length = 8, $seeds = 'alphanum')
{
  // Possible seeds
  $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
  $seedings['numeric'] = '0123456789';
  $seedings['alphanum'] = 'abcdefghijklmnopqrstuvwqyz0123456789';
  $seedings['Alphanum'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwqyz0123456789';
  $seedings['hexidec'] = '0123456789abcdef';
  
  // Choose seed
  if (isset($seedings[$seeds]))
  {
    $seeds = $seedings[$seeds];
  }
  
  // Seed generator
  list($usec, $sec) = explode(' ', microtime());
  $seed = (float) $sec + ((float) $usec * 100000);
  mt_srand($seed);
  
  // Generate
  $str = '';
  $seeds_count = strlen($seeds);
  
  for ($i = 0; $length > $i; $i++)
  {
    $str .= $seeds{mt_rand(0, $seeds_count - 1)};
  }
  
  return $str;
}

function filter(&$value) {
  $value = htmlspecialchars($value, ENT_HTML5);
}


$authentication = array();

if ($config['mongo']['enableAuth']) {
    $authentication =  array("username" => $config['mongo']['username'], "password" => $config['mongo']['password']);
}

// Create MongoDB connection
$mc = new MongoClient("mongodb://localhost", $authentication);

$db = $config['mongo']['db'];
// Select file collection
$mcFiles = $mc->selectDB($db)->selectCollection("files");
// Select stats collection
$mcStats = $mc->selectDB($db)->selectCollection("stats");



if($_SERVER['REQUEST_METHOD'] == 'PUT')
{
  // loop over possible random filename
  while (!isset($newFullName) || !empty($mcResult))
  {
    // TODO: Make this more scalable, e.g. possible to auto increase str_rand length when necessary
    $newFileName = str_rand(3, 'Alphanum');
    $newFullName = $config['path'] . $newFileName;

    // Search for file in MongoDb
    $mcQuery = array("filePath" => $newFullName);    
    $mcResult = $mcFiles->findOne($mcQuery);
  }
  
  // Receive and benefit
  try
  {
    if (!($putData = fopen("php://input", "r")))
        throw new Exception("What are you putting?");
  
    $tot_write = 0;
    $tmpFileName = tempnam('/tmp/', 'float_');
    // Create a temp file
    if (!is_file($tmpFileName))
    {
      fclose(fopen($tmpFileName, "x")); //create the file and close it
      // Open the file for writing
      if (!($fp = fopen($tmpFileName, "w")))
        throw new Exception("Can't write to tmp file");

      // Read and write the data, chunked
      while ($data = fread($putData, $config['chunkSize']))
      {
        $chunk_read = strlen($data);
        if (($block_write = fwrite($fp, $data)) != $chunk_read)
          throw new Exception("Can't write more to tmp file");

        $tot_write += $block_write;
      }

      if (!fclose($fp))
        throw new Exception("Can't close tmp file");

      unset($putData);
    }
    else
    {
      // Open the file for writing
      if (!($fp = fopen($tmpFileName, "a")))
        throw new Exception("Can't write to tmp file");
  
      // Read the data a chunk at a time and write to the file
      while ($data = fread($putData, $config['chunkSize']))
      {
        $chunk_read = strlen($data);
        if (($block_write = fwrite($fp, $data)) != $chunk_read)
            throw new Exception("Can't write more to tmp file");
  
        $tot_write += $block_write;
      }
  
      if (!fclose($fp))
        throw new Exception("Can't close tmp file");
  
      unset($putData);
    }
  
    // Store metadata

    // Check file length and sum
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file['sha1Sum'] = sha1_file($tmpFileName);
    $file['fileSize'] = $tot_write;
    $file['mimeInfo'] = finfo_file($finfo, $tmpFileName);
    $file['filePath'] = $newFullName;
    $file['fileUrl'] = $config['URL'] . $newFileName;
    $file['remoteAddress'] = getenv("REMOTE_ADDR");
    finfo_close($finfo);

    // Can we talk to our db?
    if($mc)
    {
      // Add results to array if file exists
      $result = $mcFiles->findOne(array("sha1Sum" => $file['sha1Sum']), array("_id" => 0, "filePath" => 0));
      if($result)
      {
        // We have a file with the same sha1sum
        $result = array("code" => 0, "msg" => "I already have this file :)", "result" => $result);
        // And delete the tempfile
        unlink($tmpFileName);
      }
      else
      {
        // Unknown file, so lets move it to the pub directory
        rename($tmpFileName, $newFullName);
    
        // insert the metadata in MongoDB
        $mcFiles->insert($file);
    
        // Add old metadata to result array
        $result = $file;
        $result['deleteUrl'] = $config['URL'] .  $result['_id'];
        unset($result['filePath']);
        unset($result['_id']);
        $result = array("code" => 1, "msg" => "Thank you for your file :)", "result" => $result);
      }
      // Report the info to the user
      echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }
    else
    {
      // MongoDb connection is not there :o
      die("Oh my gawwd! tErrOROROR");
    }
  }
  catch (Exception $e)
  {
    echo '', $e->getMessage(), "\n";
  }
}

// If we get HTTP GET, they probably want to download something
elseif ($_SERVER['REQUEST_METHOD'] == 'GET')
{


  // let's confirm if a filename is requested
  if (count($_GET) > 0)
  {
    // Request URL should be rewritten and file passed as argument
    if (isset($_GET['key']) && !isset($_GET['op']))
    {
      if ($mc)
      {
	
        // Let's see if we have this file
        $fileName = $_GET['key'];
        $filePath = $config['path'] . $fileName;

        // Query MongoDb for file info
        $mcQuery = array("filePath" => $filePath);
        $mcResult = $mcFiles->findOne($mcQuery);

        // If query is succesful, make sure that we can read the file from disk
        if (!empty($mcResult) && is_readable($filePath))
        {
          // We need the MIME type to pass as http header
          $fileType = $mcResult["mimeInfo"];

          // Add the X-Sendfile header with filePath so the webserver (mod_xsendfile) can server the file
          header("X-Sendfile: $filePath");
          header("Content-Description: File Transfer");
          header("Content-Type: $fileType");
          // Let's not force a download dialog, but we can with the following header
          // header("Content-Disposition: attachment; filename=\"$fileName\"");

          // Let's store this success in DB so we can show off!
          $fileId = new MongoId($mcResult["_id"]);
          $mcQuery = array("fileId" => $fileId, "fileName" => $fileName, "timeStamp" => new MongoDate(), "remoteAddress" => getenv("REMOTE_ADDR"), "remoteUserAgent" => getenv("HTTP_USER_AGENT"));
          $mcStats->insert($mcQuery);

	  echo file_get_contents($filePath);

          exit;
        }
        else
        {
          header("HTTP/1.1 404 Not Found");
          echo "No, never heared of $fileName";
        }
      }
      else
      {
        // MongoDb connection is not there :o
        die("Oh my gawwd! tErrOROROR");
      }
    }
    elseif (isset($_GET['key']) && isset($_GET['op']))
    {
      if($_GET['op'] === "stats")
      {
        $fileName = $_GET['key'];
        $filePath = $config['path'] . $fileName;

        // Query MongoDb for file info
        $mcQuery = array("filePath" => $filePath);
        $fileInfo = $mcFiles->findOne($mcQuery);

        // Query MongoDb for stats
        $fileId = $fileInfo["_id"];
        $mcQuery = array("fileId" => $fileId);
        $fileStats = $mcStats->find($mcQuery);
        $fileStats->sort(array("timeStamp" => 1));
        $statsCount = $fileStats->count();
        $fileStats = iterator_to_array($fileStats, true);
        $tpl = array(
          "fileName" => $fileName,
          "statsCount" => $statsCount,
          "stats" => $fileStats);
        array_walk_recursive($tpl, "filter");
        define('TPL', 1);
        include('../stats.inc.php');
/*        echo "Downloaded: " . $fileCount . " times<br/>\n";
        echo "<table>\n";
        echo "  <tr>\n";
        echo "    <th>Date</th>\n";
        echo "    <th>Requestor</th>\n";
        echo "    <th>User Agent</th>\n";
        echo "  </tr>\n";
        foreach ($fileStats as $stats)
        {
          echo "  <tr>\n";
          echo "    <td>" . date('c', $stats['timeStamp']->sec) . "</td>\n";
          echo "    <td>" . $stats['remoteAddress'] . "</td>\n";
          echo "    <td>" . $stats['remoteUserAgent'] . "</td>\n";
          echo "  </tr>\n";
        }
        echo "</table>\n";*/


      }
    }
    else
    {
      // Okay something is clearly wrong
      header("HTTP/1.1 405 Fuck Off");
      echo "405: Computer says \"No!\"";
    }
  }
  else
  {
    // They did a GET request but didn't request a file :/ Maybe they know we do useful stuff like telling them which IP they're using to connect to us
    printf("%s\n",getenv("REMOTE_ADDR"));
  }
}

// If we get HTTP DELETE, they want to delete stuff
elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE' && isset($_GET['key']))
{
  // Key should be MongoId
  $key = $_GET['key'];

  // MongoId should match this
  $expr = '/^[a-z0-9]{24}$/';

  if (preg_match($expr, $key))
  {
    if ($mc)
    {
      // Let's initialize this bitch as MongoId
      $fileMongoId = new MongoId($_GET['key']);

      // Does it exist?
      $mcQuery = array('_id' => $fileMongoId);
      $result = $mcFiles->findOne($mcQuery);
      
      if (($result) && is_readable($result['filePath']))
      {
        // Let's delete it \o/
        if(unlink($result['filePath']))
        {
          $result = $mcFiles->remove($mcQuery);
          $result = array('code' => 1, 'msg' => 'Thank you! The file is deleted :)');
        }
        else
        {
          $result = array('code' => 0, 'msg' => 'Not finding == not deleting that shit :(');
        }
      }
      else
      {
        $result = array('code' => 0, 'msg' => 'Not finding == not deleting that shit :(');
      }
      echo json_encode($result, JSON_UNESCAPED_SLASHES);
    }
  }
  else
  {
    header("HTTP/1.1 405 Fuck Off");
    echo "405: Computer says \"No!\"";
  }
}
?>
