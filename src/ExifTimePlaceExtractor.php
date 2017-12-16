<?php

namespace PhpExifTimePlace;


class ExifTimePlaceExtractor
{
    protected $data;
    
    public function __construct($fileName)
    {
        $logger = new \Monolog\Logger('exiftool');
        $reader = \PHPExiftool\Reader::create($logger);

        $metadatas = $reader->files($fileName)->first();
        $this->data = $metadatas->getMetadatas();

    }
    
    
    
    public function getTime($timezone)
    {
        if ($this->data->containsKey('GPS:GPSTimeStamp') && $this->data->containsKey('GPS:GPSDateStamp'))
        {
          $dateString = $this->data->get('GPS:GPSDateStamp')->getValue()->asString()." ".$this->data->get('GPS:GPSTimeStamp')->getValue()->asString();
          $myDate = \DateTime::createFromFormat("Y?m?d H:i:s", $dateString, new \DateTimeZone('GMT'));
        }
        else if ($this->data->containsKey('QuickTime:CreateDate'))
        {
          $dateString = $this->data->get('QuickTime:CreateDate')->getValue()->asString();
          $myDate = \DateTime::createFromFormat("Y?m?d H:i:s", $dateString, new \DateTimeZone('GMT'));
        }
        else if ($this->data->containsKey('H264:DateTimeOriginal'))
        {
          $dateString = $this->data->get('H264:DateTimeOriginal')->getValue()->asString();
          $myDate = new \DateTime($dateString);
        }
        else if ($this->data->containsKey('ExifIFD:DateTimeOriginal') || $this->data->containsKey('ExifIFD:CreateDate'))
        {
          if ($this->data->containsKey('ExifIFD:DateTimeOriginal'))
          {
            $dateString = $this->data->get('ExifIFD:DateTimeOriginal')->getValue()->asString();
          }
          else
          {
            $dateString = $this->data->get('ExifIFD:CreateDate')->getValue()->asString();
          }
    
          $myDate = \DateTime::createFromFormat("Y?m?d H:i:s", $dateString, new \DateTimeZone($timezone));
    
          if (($myDate === false) || (substr($myDate->format(\DateTime::ISO8601),0,1) === '-'))
          {
              throw new ExifException();
          }
        }
        else
        {
            throw new ExifException();
        }
        
        return $myDate;
    }
    
    public function getLocation()
    {
        if ($this->data->containsKey('Composite:GPSLatitude') && $this->data->containsKey('Composite:GPSLongitude'))
        {
          return array(
            'latitude' => $this->data->get('Composite:GPSLatitude')->getValue()->asString(),
            'longitude' => $this->data->get('Composite:GPSLongitude')->getValue()->asString()
          );
        }
        else 
        {
            throw new ExifException();
        }
        
    }
    
    public function decodeJsonComment()
    {
        if ($this->data->containsKey('ExifIFD:UserComment'))
        {
          $jsonText = $this->data->get('ExifIFD:UserComment')->getValue()->asString();
          $jsonText = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonText);
    
          $jsonData = json_decode($jsonText, true);
          
          if (!$jsonData){
            throw new ExifException('no json in comment');
          }
          else {
              return $jsonData;
          }
        }
        else 
        {
            throw new ExifException('no exif user-comment');
        }
    }
    
    






    /*

    foreach ($metadatas as $index => $metadata) {
      error_log('doing one metadata for key: '.$index);
        if (\PHPExiftool\Driver\Value\ValueInterface::TYPE_BINARY === $metadata->getValue()->getType()) {
            error_log($metadata->getTag());
        } else {
            error_log($metadata->getTag()."---".$metadata->getValue()->asString());
        }
    }


    */
    
    
}
