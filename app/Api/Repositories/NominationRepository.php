<?php
namespace euro_hms\Api\Repositories;
use Carbon\Carbon;
use DB;
use euro_hms\Models\Nomination;
use euro_hms\Api\Repositories\NotificationRepository;
use Excel;
use File;
use euro_hms\Api\Repositories\AgreementRepository;
use euro_hms\Api\Repositories\AvailabilityRepository;
use euro_hms\Api\Repositories\UserRepository;
use Auth;




 class NominationRepository 
 {
     public function __construct(){
        $this->agmtObj = new AgreementRepository();
        $this->avalabObj = new AvailabilityRepository();
        $this->userObj = new UserRepository();

    }
   
 	/**
 	 * [getNominationList description]
 	 * @param  [type] $userType [description]
 	 * @param  [type] $noOfPage [description]
 	 * @param  [type] $userId   [description]
 	 * @return [type]           [description]
 	 */
    public function getNominationList($userType,$noOfPage,$userId)
    {
        if($userType==6)
        {
             $list= Nomination::where('nomination_request.buyer_id',$userId)->select('nomination_request.*','nomination_request.id as nId')->orderBy('nomination_request.created_at','desc')->paginate($noOfPage);

        }
        else if($userType==7)
        {
             $list= Nomination::join('users', function ($join) {
                $join->on('users.id', '=', 'nomination_request.buyer_id');
            })->select('nomination_request.*','nomination_request.id as nId','users.first_name as buyer_name')->groupBy('nomination_request.id')->orderBy('nomination_request.created_at','desc')->paginate($noOfPage);
        }
        
        return $list;
    }

    /**
     * [create description]
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    public function create($request)
    {
    	$form_data=$request->nominationData;
        //$check_duplicate=$this->check_duplicate('ADD',0,$form_data['buyer_id'],$form_data['seller_id']);
        //if($check_duplicate=='yes')
        //{
            //$nom_id=array('nomination_id'=>0,'code'=>301);
        //}
        //else
        //{
            $nom= new Nomination;
            $nom->buyer_id=$form_data['buyer_id'];
            //$nom->seller_id=$form_data['seller_id'];
            $nom->date=$form_data['date']['time'];
            $nom->quantity_required=$form_data['quantity'];
            $nom->status=1;
            $nom->request='Pending';
            $nom->save();
            $nom_id=array('nomination_id'=>$nom->id,'code'=>200);
            $dataUserId = $form_data['buyer_id'];
            $userName = $this->userObj->getUserNameById( $dataUserId);
            $addedBy  = Auth::user()->id;
            $dataId = $nom->id;
            $qty    = $form_data['quantity'];
            $type   = 'add_notification';
            $dataText = $userName.' new nomination for '.$qty.'added';
            $title  = 'Nomination request added';
            $dataTable = 'nomination_request';
            $this->notificationObj = new NotificationRepository();
            $this->notificationObj->insert($dataId,$type,$dataUserId,$dataText,$title,$dataTable,$addedBy);


        //}
        return $nom_id;
    }

    /**
     * [getNominationDetailsById description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getNominationDetailsById($id)
    {
        return Nomination::where('id',$id)->first();
    }

    /**
     * [edit description]
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    public function edit($request)
    {
        $form_data=$request->nominationData;
        $id=$form_data['nominationId'];
        //$check_duplicate=$this->check_duplicate('EDIT',$id,$form_data['name'],$form_data['type']);
        //if($check_duplicate=='yes')
       // {
            //$lab_id=array('nomination_id'=>0,'code'=>301);
        //}
        //else
        //{

            $get_buyer=$this->getNominationDetailsById($id);
            $check_quantity=$this->check_quantity($form_data['approved_quantity'],$get_buyer->buyer_id);
            $check_availability=$this->check_availability($form_data['approved_quantity'],$id);
            
            if($check_availability=='yes')
            {
                $nom_id=array('nomination_id'=>'','code'=>302);
            }
            else if($check_quantity=='yes')
            {
                $nom_id=array('nomination_id'=>'','code'=>301);
            }
            else
            {
                $nom= Nomination::findOrFail($id);
                $nom->date=$form_data['date']['time'];
                $nom->quantity_required=$form_data['quantity'];
                $nom->approved_quantity=$form_data['approved_quantity'];
                $nom->status=1;
                if(Auth::user()->user_type==6)
                {
                    $nom->request='Pending';
                }
                else
                {
                    $nom->request=$form_data['request'];
                }
                
                $nom->save();
                $nom_id=array('nomination_id'=>$nom->id,'code'=>200);
                $dataUserId = $get_buyer->buyer_id;
                $userName = $this->userObj->getUserNameById( $dataUserId);
                $dataId = $nom->id;
                $qty    = $form_data['quantity'];
                $type   = 'update_notification';
                $dataText =  $userName.' update notification for '.$qty;
                $title  = 'Nomination request updated';
                $addedBy  = Auth::user()->id;
                $userType = Auth::user()->user_type;
                $dataTable = 'nomination_request';
                $this->notificationObj = new NotificationRepository();
                $this->notificationObj->insert($dataId,$type,$dataUserId,$dataText,$title,$dataTable,$addedBy);

                if($userType == 7){
                    $dataUserId1 = $nom->buyer_id;
                    $approveQty = $form_data['approved_quantity'];
                    $reuestType = $form_data['request'];
                    $acualQty = $form_data['quantity'];
                $dataText1 = $userName.' quantity of '.$acualQty.' chnaged to '.$reuestType.''.$approveQty;
                 
                      $title1  = 'Request quantity'.$reuestType;
                      $type1   = 'update_request_qty_status';

                    $this->notificationObj->insert($dataId,$type1,$dataUserId1,$dataText1,$title1,$dataTable,$addedBy);
                }

            }
        //}
       
        return  $nom_id;
    }
    /**
     * [check_quantity description]
     * @param  [type] $approved_quantity [description]
     * @param  [type] $buyer_id          [description]
     * @return [type]                    [description]
     */
    public function check_quantity($approved_quantity,$buyer_id)
    {

        $allowed_quantity=$this->agmtObj->getAllowedQuantityByBuyerId($buyer_id);
        $total_quantity=($allowed_quantity*120)/100;
        if($approved_quantity>$total_quantity)
        {
            return 'yes';
        }
        else
        {
            return 'no';
        }
        return 'no';
    }

    public function check_availability($approved_quantity,$id)
    {
        $date=Carbon::now()->addDays(1)->format('Y-m-d');
        $availability=$this->avalabObj->getAvailability($date);
        $total_quantity=Nomination::whereDate('date',$date)->whereRaw('id != ?',$id)->select([DB::raw('SUM(approved_quantity) as total_approved_quantity')])->get();
        $total=$total_quantity[0]['total_approved_quantity']+$approved_quantity;
        //echo $total.'||'.$availability;exit;
        if($total>$availability)
        {
            return 'yes';
        }
        else
        {
            return 'no';
        }
        return 'no';
    }

    /**
     * [check_duplicate description]
     * @param  [type] $page_name [description]
     * @param  [type] $id        [description]
     * @param  [type] $name      [description]
     * @param  [type] $type      [description]
     * @return [type]            [description]
     */
    public function check_duplicate($page_name,$id,$name,$type)
    {
        if($page_name=='ADD')
        {
            $get_nomination=Nomination::whereRaw('LOWER(name)  = ?', $name)->whereRaw('LOWER(type)  = ?', $type)->get();
            if(count($get_nomination)>0)
            {
                return 'yes';
            }
            else
            {
                 return 'no';
            }
        }
        else if($page_name=='EDIT')
        {
            $get_nomination=Nomination::whereRaw('LOWER(name) = ?', $name)->whereRaw('LOWER(type)  = ?', $type)->whereRaw('id != ?',$id)->get();
            if(count($get_nomination)>0)
            {
                return 'yes';
            }
            else
            {
                 return 'no';
            }
        }
        return 'no';
    }

    /**
     * [delete description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function delete($id)
    {
        $presp_id = Nomination::find( $id );
        $presp_id ->delete();
        return $id;
    }

    /**
     * [getNominationDetailsByDate description]
     * @param  [type] $date [description]
     * @return [type]       [description]
     */
    public function getNominationDetailsByDate($date)
    {

        $new_date=Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');

        $list= Nomination::join('users', function ($join) {
                $join->on('users.id', '=', 'nomination_request.buyer_id');
            })->whereDate('date',$new_date)->get();
        return $list;

    }

    /**
     * [getNominationRequestList description]
     * @return [type] [description]
     */
    public function getNominationRequestList(){
        return Nomination::whereIn('request',['Pending','Approved'])->groupBy('buyer_id')->get();
    }

    /**
     * [getTotalApprovedQuantity description]
     * @param  [type] $date [description]
     * @return [type]       [description]
     */
    public function getTotalApprovedQuantity($date)
    {
        $availability=Nomination::whereDate('date',$date)->select([DB::raw('SUM(approved_quantity) as total_approved_quantity')])->first();
        return $availability->total_approved_quantity;
    }

     public function getTotalSuppliedQuantity($date)
    {
        $availability=Nomination::whereDate('date',$date)->select([DB::raw('SUM(supplied_quantity) as total_supplied_quantity')])->first();
        return $availability->total_supplied_quantity;
    }
    
 }
?>
