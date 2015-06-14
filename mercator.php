<?php
/* * *************************************************************
 * Copyright notice
 *
 * (c) 2014-2015 Chi Hoang (info@phpdevpad.de)
 *  All rights reserved
 *
 * **************************************************************/

class mercator {

   var $mapWidth;
   var $mapHeight;
   var $mapLonLeft;
   var $mapLatBottom;
   var $mapLonRight;
   var $mapLatTop;
   var $set;
   var $proj;
   var $ZMax;
   var $ZMin;
   
   function __construct ($mapWidth=1000,$mapHeight=1000) 
   {
       $this->mapWidth    = $mapWidth; 
       $this->mapHeight   = $mapHeight; 
       $this->mapLonLeft  = 5000; 
       $this->mapLatBottom= 5000; 
       $this->mapLonRight =-5000; 
       $this->mapLatTop   =-5000; 
       $this->set=array(); 
       $this->proj=array();
       $this->ZMax        =-5000;
       $this->ZMin        =5000;
   }
  
   //function convertPixelToGeo(tx:Number, ty)
   //{
   //   $LatBottomSin=min(max(sin($this->mapLatBottom*(M_PI/180)),-0.9999),0.9999);
   //   $worldMapWidth=(($this->mapWidth/$mapLonDelta)*360)/(2*M_PI);
   //   
   //   $worldMapRadius = $mapWidth / $mapLonDelta * 360/(2*M_PI);     
   //   $mapOffsetY = ($worldMapRadius/2 *log((1+sin($LatBottomSin))/(1-sin($LatBottomSin))));
   //   $equatorY = $this->mapHeight + mapOffsetY;   
   //   $a = ($equatorY-$ty)/$worldMapRadius;
   //
   //   $lat = 180/Math.PI * (2 * Math.atan(Math.exp($a)) - Math.PI/2);
   //   $long = $this->mapLonLeft+$tx/$mapWidth*$mapLonDelta;
   //   return new Point($lat,$long);
   //}

   function resize($pts) {
      $mapXLeft=180;
      $mapXRight=-180;
      $mapYBottom=180;
      $mapYTop=-180;
      
      foreach ($pts as $key=>$arr) {
	 list($lon,$lat,$z)=explode(",",$arr);
	 $mapXLeft=min($mapXLeft,$lon); 
	 $mapXRight=max($mapXRight,$lon); 
	 $mapYBottom=min($mapYBottom,$lat); 
	 $mapYTop=max($mapYTop,$lat); 
      }
      
      $lonC=($mapXLeft+$mapXRight)/2;
      $latC=($mapYBottom+$mapYTop)/2;
      
      foreach ($pts as $key=>$arr) {
	 list($lon,$lat,$z)=explode(",",$arr);
	 $lon=BETA*($lon-$lonC)+$lonC;
	 $lat=BETA*($lat-$latC)+$latC;
	 $pts[$key]="$lon,$lat,$z";
      }
      return $pts;
   }

   function loadfile($filename) {
      $arr=array();
      $file = fopen($filename, "r");
      while (!feof($file))
      {
         list($lon,$lat)=explode(",",rtrim(fgets($file)));
         $arr[]="$lon,$lat"; 
      }
      fclose($file);
      return $arr;
   }
   
   function loadfileZ($filename) {
      $arr=array();
      $file = fopen($filename, "r");
      while (!feof($file))
      {
         list($lon,$lat,$z)=explode(",",rtrim(fgets($file)));
         //if ($z===null) {
         //   $z=rand(MINRAND,MAXRAND);
         //}
         $arr[]="$lon,$lat,$z"; 
      }
      fclose($file);
      return $arr;
   }
   
   function repair($arr,$mean=0) {
      $c=count($arr)%2;
      if ($c!=0) {
	 unset($arr[count($arr)-1]);
      }
      //clockwise
      $temp=array();
      for($i=0,$end=count($arr);$i<$end;$i++) {
	 list($x1,$y1,$z)=explode(",",$arr[$i]);
	 
	 if ($x1!=0 && $y1!=0)
	 {
	    if ($mean!=0) {
	       $temp[]=array($x1,$y1,$mean);
	    } else {
	       $temp[]=array($x1,$y1,$z);
	    }
	 }
	 list($x1,$y1,$z)=explode(",",$arr[$i+1]);
	 if ($x1!=0 && $y1!=0)
	 {
	    if ($mean!=0) {
	       $temp[]=array($x1,$y1,$mean);
	    } else {
	       $temp[]=array($x1,$y1,$z);
	    }
	 } else {
	    list($x1,$y1,$z)=explode(",",$arr[0]);
	    if ($x1!=0 && $y1!=0)
	    {
	       if ($mean!=0) {
		  $temp[]=array($x1,$y1,$mean);
	       } else {
		  $temp[]=array($x1,$y1,$z);
	       }
	    }
	 }
      }
      return $temp;
   }
   
   function convert($arr) {
      $set=array();
      foreach ($arr as $key => $arr) {
         list($lon,$lat,$z)=$arr;
	 $set[]="$lon,$lat,$z";
      }
      return $set;
   }
   
   function filter($arr,$param) {
      $x=$y=array();
      foreach ($arr as $key => $arr)
      {
         list($tx,$ty) = $arr;
         $x[]=$tx;
         $y[]=$ty;
      }
      $x=array_values($x);
      $y=array_values($y);
      $set=array();
      for($i=0,$end=count($x);$i<$end;$i+=$param)
      {
	 $tx=round($x[$i]);
	 $ok=0;
	 foreach ($set as $key => $arr)
	 {
	    list($x1,$y1)=explode(",",$arr);
	    if($tx==$x1)
	    {
	       $ok=1;
	       break;
	    }
	 }
	 if ($ok==0)
	 {
	    $set[$i]="$tx,".round($y[$i]);
	 }
      }
      $filter=array();
      foreach ($set as $key => $arr) {
         list($lon,$lat,$z)=explode(",",$this->set[$key]);
         $filter[]="$lon,$lat,$z";
      }
      return $filter;
   }
   
   function project($arr,$mean=0) 
   {
      $this->mapLonLeft  = 5000; 
      $this->mapLatBottom= 5000; 
      $this->mapLonRight =-5000; 
      $this->mapLatTop   =-5000; 
      $this->proj=array();
      $this->ZMax        =-5000;
      $this->ZMin        =5000;
       
      $sum=$c=0;
      foreach ($arr as $key => $arr2) 
      { 
         list($lon,$lat,$z) = explode(",",$arr2);
         $this->ZMin=min($this->ZMin,$z);
	 $this->ZMax=max($this->ZMax,$z);
	 $sum+=$z;
	 $c++;
         $this->mapLonLeft=min($this->mapLonLeft,$lon); 
         $this->mapLonRight=max($this->mapLonRight,$lon); 
         $this->mapLatBottom=min( $this->mapLatBottom,$lat); 
         $this->mapLatTop=max($this->mapLatTop,$lat); 
	 if ($mean==0) {
            $this->set[]=array($lon,$lat,$z);
         }
      } 

      $mapLonDelta=$this->mapLonRight-$this->mapLonLeft; 
      $mapLatDelta=$this->mapLatTop-$this->mapLatBottom; 

      $mapLatTopY= $this->mapLatTop*(M_PI/180); 
      $worldMapWidth=(($this->mapWidth/$mapLonDelta)*360)/(2*M_PI); 
      $LatBottomSin=min(max(sin($this->mapLatBottom*(M_PI/180)),-0.9999),0.9999); 
      $mapOffsetY=$worldMapWidth/2 * log((1+$LatBottomSin)/(1-$LatBottomSin)); 
      $LatTopSin=min(max(sin($this->mapLatTop*(M_PI/180)),-0.9999),0.9999); 
      $mapOffsetTopY=$worldMapWidth/2 * log((1+$LatTopSin)/(1-$LatTopSin)); 
      $mapHeightD=$mapOffsetTopY-$mapOffsetY; 
      $mapRatioH=$this->mapHeight/$mapHeightD; 
      $newWidth=$this->mapWidth*($mapHeightD/$this->mapHeight); 
      $mapRatioW=$this->mapWidth/$newWidth; 
      $this->mapHeight=$mapHeightD;

      foreach ($this->set as $key => $arr2) 
      { 
         list($lon,$lat,$z) = $arr2; 
         $tx = ($lon-$this->mapLonLeft)*($newWidth/$mapLonDelta)*$mapRatioW; 
         $f = sin($lat*M_PI/180); 
         $ty = ($mapHeightD-(($worldMapWidth/2 * log((1+$f)/(1-$f)))-$mapOffsetY)); 
	 
         if ($mean!=0) {
            $this->proj[]=array($tx,$ty,$mean);
         } else {
            $this->proj[]=array($tx,$ty,$z);
         }

      }
      
      $this->set=$this->convert($this->set);          
      if ($mean!=0) {
         return $mean;
      } else {
         return $sum/$c;
      }
   }
}
?>