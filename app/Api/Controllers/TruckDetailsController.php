<?php

namespace Energy\Api\Controllers;


use Illuminate\Routing\Controller;
use Energy\Models\TruckDetails;
use Illuminate\Http\Request;
use Energy\Models\User;
use Energy\Models\TruckDetails;
use Energy\Api\Repositories\TruckDetailsRepository;
use DB;
use Carbon\Carbon;

class TruckDetailsController extends Controller
{
    public function __construct(){
        $this->truckObj = new TruckDetailsRepository();
    }
    
    /**
     * [getTruckDetailsList description]
     * @return [type] [description]
     */
    public function getTruckDetailsList()
    {
        $truck_list=$this->truckObj->getTruckDetailsList();
        if($truck_list)
        {
            return ['code' => 200 ,'data'=>$truck_list,'message'=>'Getting case type successfully.'];
        }
        else
        {
            return ['code'=> 300 ,'data'=>'','message'=>'Something went wrong'];
        }
       
    }

    

   
}