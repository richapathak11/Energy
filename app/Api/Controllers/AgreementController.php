<?php

namespace euro_hms\Api\Controllers;


use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use euro_hms\Models\User;
use euro_hms\Models\Agreement;
use euro_hms\Api\Repositories\AgreementRepository;
use DB;
use Carbon\Carbon;

class AgreementController extends Controller
{
    public function __construct(){
        $this->agtObj = new AgreementRepository();

      //  $this->notificationOBJ = new NotificationRepository();
    }

     /**
     * [getAllowedQuantityByBuyerId description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getAllowedQuantityByBuyerId(Request $request)
    {
        $userId = $request->userId;
        $get_details=$this->agtObj->getAllowedQuantityByBuyerId($userId);
        if($get_details)
        {
            return ['code' => 200 ,'data'=>$get_details,'message'=>'Nomination successfully added.'];
        }
        else
        {
            return ['code'=> 300 ,'data'=>'','message'=>'Something went wrong'];
        }
    }

    

    
    
}
