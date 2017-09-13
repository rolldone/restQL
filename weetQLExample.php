<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserAboutInfo;
use App\UserExpInfo;
use App\UserEduInfo;
use App\UserSkillInfo;
use App\UserLangInfo;
use App\CompanyAboutInfo;
use App\Follow;
use App\PostJobInfo;
use App\Image;
use App\MetaLocation;
use App\FriendList;
use App\FieldOfStudy;
use App\Skill;
use Storage;
use Hash;
use Validator;
use App\Classes\weetQL\weetQLController;
class weetQL extends Controller
{
   	use weetQLController;
    
    // authentication
    public function authenticationMember($q,$obje,$request){
        $request->request->add(['username' => $obje->fieldCheck->username]);
        $request->request->add(['password' => $obje->fieldCheck->password]);
        $auth = \Auth::guard('member');
        $credentials = null;
        if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
            $credentials = [
                'email'=>$request->get('username'),
                'password' =>  $request->get('password'),
            ];
        }else{
            $credentials = [
                'username'=>$request->get('username'),
                'password' =>  $request->get('password'),
            ];
        }
        
        if ($auth->attempt($credentials)) {
            return response()->json([
                'status'=>'success',
                'message'=>'Successfully!',
                'user'=>$auth->user()
            ],200);
        }
    
        return response()->json([
            'status'=>'rejected',
            'message'=>'Invalid Credentials!'
        ],401);
    }
    // define your component at here

    // get current id auth for table relation
    public function getCurrentId($q){
        $auth = \Auth::guard('member');
        if($auth != null){
            return $q->where('member_id','=',$auth->user()->id);
        }
    }
    
    public function getCheckingYouAreMember(){
        $auth = \Auth::guard('member');
        if($auth != null){
            return true;
        }
        return false;
    }
    
    public function newRequestConnectionUser($q,$obje,$request){
        $request->request->add(['follower_id' => $obje->fieldCheck->follower_id]);
        $request->request->add(['request_option' => $obje->fieldCheck->request_option]);
        $validator = Validator::make($request->all(), [
            'follower_id'     => 'required',
            'request_option' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $auth = \Auth::guard('member');
        if($auth->user() == null){
            return response()->json([
                'status'=>'error',
                'message'=> 'User No yet u'
            ],500); 
        }
        switch($request->request_option){
            case 'follow':
                $q = Follow::where('follower_id','=',$request->follower_id)->first();
                if($q == null){
                   $q = new Follow();
                }
                $q->status = 1;
                break;
            case 'unfollow':
                $q = Follow::where('follower_id','=',$request->follower_id)->first();
                $q->status = 2;
                break;
            case 'block':
                $q = Follow::where('follower_id','=',$request->follower_id)->first();
                $q->status = 3;
                break;
        }
        $q->member_id = $auth->user()->id;
        $q->follower_id = $request->follower_id;
        $q->save();
        return response()->json([
            'status'=>'success',
            'message'=> 'New Requets Successfully!'
        ],200);
    }
    
    // get current id for User model
    public function getCurrentUserId($q){
    	$auth = \Auth::guard('member');
        return $q->where('id','=',$auth->user()->id);
    }
    
    public function spesificForCompanyList($q,$obje,$request){
        $q = $q->with('hasHaveCompanyAboutInfo');
        if(isset($q->fieldCheck->company_name)){
            $q = $q->whereHas('hasHaveCompanyAboutInfo',function($qq) use($q){
               $qq->where('company_name','LIKE','%'.$q->fieldCheck->company_name.'%'); 
            });
        }
        if(isset($q->fieldCheck->location_from)){
            $q = $q->whereHas('hasHaveCompanyAboutInfo',function($qq) use($q){
               $qq->where('location_from','LIKE','%'.$q->fieldCheck->location_from.'%'); 
            });
        }
    }
    
    public function spesificForUserList($q,$obje,$request){
        $q = $q->with('hasHaveUserAboutInfo');
        if(isset($q->fieldCheck->name)){
            $q = $q->whereHas('hasHaveUserAboutInfo',function($qq) use($q){
               $qq->where('name','LIKE','%'.$q->fieldCheck->name.'%'); 
            });
        }
        if(isset($q->fieldCheck->location_from)){
            $q = $q->whereHas('hasHaveUserAboutInfo',function($qq) use($q){
               $qq->where('location_from','LIKE','%'.$q->fieldCheck->location_from.'%'); 
            });
        }
    }
    
    public function checkReadyApllyJob($q,$obje,$request){
        $auth = \Auth::guard('member');
        if($auth == null){
            return $q;
        }
        $q = $q->whereRaw("json_contains(apply_id->'$[*]',json_array(".$auth->user()->id."))");
        $q = $q->where('id','=',$obje->fieldCheck->id_postjob)->first();
        if($q==null){
            return response()->json([
                'status'=>'empty',
                'message'=> 'No yet apply!'
            ],500); 
        }
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
        
    }
    public function applyJobProcess($q,$obje,$request){
        $auth = \Auth::guard('member');
        if($auth == null){
            return response()->json([
                'status'=>'rejected',
                'message'=> 'User No yet u'
            ],500); 
        }
        $memberApplied = PostJobInfo::whereRaw('json_contains(apply_id->"$[*]",json_array('.$auth->user()->id.'))')->first();
        if($memberApplied != null){
            return response()->json([
                'status'=>'rejected',
                'message'=> 'User was applied!'
            ],500); 
        }
        $q = $q->where('id','=',$obje->fieldCheck->id_postjob)->first();
        $gg = $q->apply_id;
        if($gg == null){
            $gg = [];
        }
        array_push($gg,$auth->user()->id);
        $q->apply_id = $gg;
        $q->save();
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    
    public function spesificForJobList($q,$obje,$request){
        $q = $q->with('fromUserCompany')->with('fromUserCompany.hasHaveCompanyAboutInfo');
        // $request->request->add(['parent_id' => $obje->fieldCheck->parent_id]);
        if(isset($obje->fieldCheck->job_name)){
            $q = $q->where('job_name','LIKE','%'.$obje->fieldCheck->job_name.'%');
        }
        if(isset($obje->fieldCheck->location)){
            // $id_locations = MetaLocation::with('')
            $q = $q->whereRaw('json_contains(location->"$[*].relation_id[*]",json_array('.$obje->fieldCheck->location.'))');
            /*
            $gg = json_decode($obje->fieldCheck->location);
            $q = $q->whereRaw('json_contains(location->"$[*].relation_id",json_array('.$gg[0].')');
            for($a=1;$a<count($gg);$a++){
                $q = $q->orWhereRaw('json_contains(location->"$[*].relation_id",json_array('.$gg[$a].')');
            }*/
        }
        if(isset($obje->fieldCheck->category_id)){
            if($obje->fieldCheck->category_id != ''){
                $q = $q->where('category_id','=',$obje->fieldCheck->category_id);
            }
        }
        $q = $q->get();
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    
    public function getCoverPhotoUser($q){
        $auth = \Auth::guard('member');
        $UserAboutInfo = new UserAboutInfo();
        $gg = UserAboutInfo::where('member_id','=',$auth->user()->id)->first();
        return $q->where('table_name','=',$UserAboutInfo->table)
            ->where('table_id','=',$gg->id)
            ->where('field_name','=',CompanyAboutInfo::$header_field);
    }
    
    public function newFriendRequest($q,$obje,$request){
        $request->request->add(['member_id' => $obje->fieldCheck->member_id]);
        $request->request->add(['target_member_id' => $obje->fieldCheck->target_member_id]);
        $request->request->add(['action_user_id' => $obje->fieldCheck->action_user_id]);
        $validator = Validator::make($request->all(), [
            'member_id' => 'required',
            'target_member_id'     => 'required',
            'action_user_id'     => 'required',
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $q = new FriendList();
        $auth = \Auth::guard('member');
        if($auth == null){
            return response()->json([
                'status'=>'error',
                'message'=> 'User No yet u'
            ],500); 
        }
        
    }
    public function updateSettingCompany($q,$obje,$request){
        $request->request->add(['email' => $obje->fieldCheck->email]);
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:user_companies,email'
        ]);
        if($validator->fails()){
            return (object) array(
                'status'=>'error',
                'message'=> $validator->errors()
            );
        }
    }
    public function saveUserEduInfo($q,$obje,$request){
        // check requirement
        $result = $this->getRequireFilter($obje,['getCurrentUserAboutInfo']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        if(isset($obje->fieldCheck->id)){
            $q = $q->where('id','=',$obje->fieldCheck->id);
        }
        $q = $q->first();
        if($q == null){
            $q = new UserEduInfo();
            $auth = \Auth::guard('member');
            $q->member_id = $auth->user()->id;
        }
        try{
            if(isset($obje->fieldCheck->school_name)){
                $q->school_name = $obje->fieldCheck->school_name;
            }
            if(isset($obje->fieldCheck->graduate)){
                $q->graduate = $obje->fieldCheck->graduate;
            }
            if(isset($obje->fieldCheck->field_of_study)){
                $q->field_of_study = $obje->fieldCheck->field_of_study;
            }
            if(isset($obje->fieldCheck->start)){
                $q->start = $obje->fieldCheck->start;
            }
            if(isset($obje->fieldCheck->end)){
                $q->end = $obje->fieldCheck->end==null?NULL:$obje->fieldCheck->end;
            }
            if(isset($obje->fieldCheck->until_now)){
                $q->until_now = $obje->fieldCheck->until_now;
            }
            if(isset($obje->fieldCheck->place)){
                $q->place = $obje->fieldCheck->place;
            }
            
            $q->save();
            
            return response()->json([
                'status'=>'success',
                'message'=> 'Edu updated!'
            ],200); 
        }catch(Exception $ex){
            return response()->json([
                'status'=>'error',
                'message'=> $ex->getMessage()
            ],500); 
        }
    }
    public function newUserEduInfo($q,$obje,$request){
        $request->request->add(['school_name' => $obje->fieldCheck->school_name]);
        $request->request->add(['graduate' => $obje->fieldCheck->graduate]);
        $request->request->add(['field_of_study' => $obje->fieldCheck->field_of_study]);
        $request->request->add(['start' => $obje->fieldCheck->start]);
        $request->request->add(['end' => $obje->fieldCheck->end]);
        $request->request->add(['until_now' => $obje->fieldCheck->until_now]);
        $request->request->add(['place' => $obje->fieldCheck->place]);
        $validator = Validator::make($request->all(), [
            'school_name' => 'required',
            'graduate'     => 'required',
            'field_of_study'     => 'required',
            'start'     => 'required',
            // 'end'     => 'required',
            'until_now' => 'required',
            'place' => 'required',
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $q = new UserEduInfo();
        $auth = \Auth::guard('member');
        if($auth == null){
            return response()->json([
                'status'=>'error',
                'message'=> 'User No yet u'
            ],500); 
        }
        $q->member_id = $auth->user()->id;
        try{
            $q->school_name = $obje->fieldCheck->school_name;
            $q->graduate = $obje->fieldCheck->graduate;
            $q->field_of_study = $obje->fieldCheck->field_of_study;
            $q->start = $obje->fieldCheck->start;
            $q->end = $obje->fieldCheck->end==null?NULL:$obje->fieldCheck->end;
            $q->until_now = $obje->fieldCheck->until_now;
            $q->place = $obje->fieldCheck->place;
            $q->save();
            $q = UserEduInfo::where('id','=',$q->id)->first();
            $q = [
                'id' => $q->id
            ];
            return response()->json([
                'status'=>'success',
                'message'=> $q
            ],200); 
        }catch(Exception $ex){
            return response()->json([
                'status'=>'error',
                'message'=> $ex->getMessage()
            ],500); 
        }
    }
    public function newUserExpInfo($q,$obje,$request){
        $request->request->add(['job_skill' => $obje->fieldCheck->job_skill]);
        $request->request->add(['job_description' => $obje->fieldCheck->job_description]);
        $request->request->add(['company' => $obje->fieldCheck->company]);
        $request->request->add(['company_id' => $obje->fieldCheck->company_id]);
        $request->request->add(['start' => $obje->fieldCheck->start]);
        $request->request->add(['end' => $obje->fieldCheck->end]);
        $request->request->add(['until_now' => $obje->fieldCheck->until_now]);
        $request->request->add(['place' => $obje->fieldCheck->place]);
        $validator = Validator::make($request->all(), [
            'job_skill' => 'required',
            'job_description'     => 'required',
            'company'     => 'required',
            'start'     => 'required',
            // 'end'     => 'required',
            'until_now' => 'required',
            'place' => 'required',
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $q = new UserExpInfo();
        $auth = \Auth::guard('member');
        if($auth == null){
            return response()->json([
                'status'=>'error',
                'message'=> 'User No yet u'
            ],500); 
        }
        $q->member_id = $auth->user()->id;
        try{
            $q->job_skill = $obje->fieldCheck->job_skill;
            $q->job_description = $obje->fieldCheck->job_description;
            $q->company = $obje->fieldCheck->company;
            $q->company_id = $obje->fieldCheck->company_id==null?NULL:$obje->fieldCheck->company_id;
            $q->start = $obje->fieldCheck->start;
            $q->end = $obje->fieldCheck->end==null?NULL:$obje->fieldCheck->end;
            $q->until_now = $obje->fieldCheck->until_now;
            $q->place = $obje->fieldCheck->place;
            $q->save();
            $q = UserExpInfo::with('hasCompanyId')->where('id','=',$q->id)->first();
            $q = [
                'id' => $q->id,
                'hasCompanyId' => $q->hasCompanyId
            ];
            return response()->json([
                'status'=>'success',
                'message'=> $q
            ],200); 
        }catch(Exception $ex){
            return response()->json([
                'status'=>'error',
                'message'=> $ex->getMessage()
            ],500); 
        }
    }
    public function checkJobHasSaved($q,$obje,$request){
        $result = $this->getRequireFilter($obje,['getCurrentUserAboutInfo']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        $q = $q->whereRaw('json_contains(job_saved_id->"$[*]",json_array('.(int)$obje->fieldCheck->job_saved_id.'))')->first();
        if($q == null){
            return response()->json([
                'status'=>'empty',
                'message'=> $q
            ],500); 
        }
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    public function saveUserExpInfo($q,$obje,$request){
        // check requirement
        $result = $this->getRequireFilter($obje,['getCurrentUserAboutInfo']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        if(isset($obje->fieldCheck->id)){
            $q = $q->where('id','=',$obje->fieldCheck->id);
        }
        $q = $q->first();
        if($q == null){
            $q = new UserExpInfo();
            $auth = \Auth::guard('member');
            $q->member_id = $auth->user()->id;
        }
        try{
            if(isset($obje->fieldCheck->job_skill)){
                $q->job_skill = $obje->fieldCheck->job_skill;
            }
            if(isset($obje->fieldCheck->job_description)){
                $q->job_description = $obje->fieldCheck->job_description;
            }
            if(isset($obje->fieldCheck->company)){
                $q->company = $obje->fieldCheck->company;
            }
            if(isset($obje->fieldCheck->company_id)){
                $q->company_id = $obje->fieldCheck->company_id;
            }
            if(isset($obje->fieldCheck->start)){
                $q->start = $obje->fieldCheck->start;
            }
            if(isset($obje->fieldCheck->end)){
                $q->end = $obje->fieldCheck->end==null?NULL:$obje->fieldCheck->end;
            }
            if(isset($obje->fieldCheck->until_now)){
                $q->until_now = $obje->fieldCheck->until_now;
            }
            if(isset($obje->fieldCheck->place)){
                $q->place = $obje->fieldCheck->place;
            }
            
            $q->save();
            $q = UserExpInfo::with('hasCompanyId')->where('id','=',$q->id)->first();
            $q = $q->hasCompanyId;
            return response()->json([
                'status'=>'success',
                'message'=> $q
            ],200); 
        }catch(Exception $ex){
            return response()->json([
                'status'=>'error',
                'message'=> $ex->getMessage()
            ],500); 
        }
    }
    public function saveUserAboutInfo($q,$obje,$request){
        // check requirement
        $result = $this->getRequireFilter($obje,['getCurrentUserAboutInfo']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        $q = $q->first();
        if(isset($obje->fieldCheck->about_me_desk)){
            $q->about_me_desk = $obje->fieldCheck->about_me_desk;
        }
        if(isset($obje->fieldCheck->email)){
            $q->email = $obje->fieldCheck->email;
        }
        if(isset($obje->fieldCheck->current_job)){
            $q->current_job = $obje->fieldCheck->current_job;
        }
        if(isset($obje->fieldCheck->location_from)){
            $q->location_from = $obje->fieldCheck->location_from;
        }
        if(isset($obje->fieldCheck->birthday)){
            $q->birthday = $obje->fieldCheck->birthday;
        }
        if(isset($obje->fieldCheck->full_name)){
            $q->full_name = $obje->fieldCheck->full_name;
        }
        if(isset($obje->fieldCheck->graduate)){
            $q->graduate = $obje->fieldCheck->graduate;
        }
        if(isset($obje->fieldCheck->job_saved_id)){
            $uu = UserAboutInfo::whereRaw('json_contains(job_saved_id->"$[*]",json_array('.$obje->fieldCheck->job_saved_id.'))')->first();
            if($uu == null){
                $uu = $q->job_saved_id;
                if($uu == null){
                    $uu = [];
                }
                array_push($uu,(int)$obje->fieldCheck->job_saved_id);
                $q->job_saved_id = $uu;
            }
        }
        $q->save();
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    public function saveUser($q,$obje,$request){
        $q = $q->first();
        if(isset($obje->fieldCheck->name)){
            $q->name = $obje->fieldCheck->name;
        }
        $q->save();
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    public function searchCompany($q,$obje,$request){
        $request->request->add(['company_name' => $obje->fieldCheck->company_name]);
        $validator = Validator::make($request->all(), [
            'company_name' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $whatSearch = $request->company_name;
        $q = $q->with('hasProfileImage');
        $q = $q->with('fromUser')->whereHas('fromUser',function($q){
            $q->where('role','=',1);
        });
        $q = $q->where('company_name','like',$whatSearch.'%');
        $q = $q->get();
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    public function saveCompanyAboutInfo($q,$obje,$request){
        // check requirement
        $result = $this->getRequireFilter($obje,['getCurrentCompanyAboutInfo']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        $q = $q->first();
        if(isset($obje->fieldCheck->company_name)){
            $q->company_name = $obje->fieldCheck->company_name;
        }
        if(isset($obje->fieldCheck->email)){
            $q->email = $obje->fieldCheck->email;
        }
        if(isset($obje->fieldCheck->product_name)){
            $q->product_name = $obje->fieldCheck->product_name;
        }
        if(isset($obje->fieldCheck->about_company)){
            $q->about_company = $obje->fieldCheck->about_company;
        }
        if(isset($obje->fieldCheck->why_join_us)){
            $q->why_join_us = $obje->fieldCheck->why_join_us;
        }
        if(isset($obje->fieldCheck->location_from)){
            $q->location_from = $obje->fieldCheck->location_from;
        }
        $q->save();
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],200); 
    }
    public function checkValidEmail($q,$obje,$request){
        $request->request->add(['email' => $obje->fieldCheck->email]);
        $current_email = null;
        if(isset(\Auth::guard('member')->user()->email)){
            $current_email = \Auth::guard('member')->user()->email;

        }
        if($current_email == null){
            if(isset(\Auth::guard('company')->user()->email)){
                $current_email = \Auth::guard('company')->user()->email;    
            }
        }
        if($request->get('email') != $current_email){
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:user_companies,email'
            ]);
            if($validator->fails()){
                return $validator->errors();
            }
        }
    }
    public function savePostJobInfo($q,$obje,$request){
        // check requirement
        $result = $this->getRequireFilter($obje,['getCurrentCompanyAboutInfo']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        if(isset($obje->fieldCheck->id)){
            $q = $q->where('id','=',$obje->fieldCheck->id);
        }
        $q = $q->first();
        if($q == null){
            $q = new PostJobInfo();
            $auth = \Auth::guard('member');
            $q->member_id = $auth->user()->id;
        }
        if(isset($obje->fieldCheck->job_name)){
            $q->job_name = $obje->fieldCheck->job_name;
        }
        if(isset($obje->fieldCheck->job_description)){
            $q->job_description = $obje->fieldCheck->job_description;
        }
        if(isset($obje->fieldCheck->category_id)){
            $q->category_id = $obje->fieldCheck->category_id;
        }
        if(isset($obje->fieldCheck->location)){
            $q->location = $obje->fieldCheck->location;
        }
        if(isset($obje->fieldCheck->max_salary)){
            $q->max_salary = $obje->fieldCheck->max_salary;
        }
        if(isset($obje->fieldCheck->min_salary)){
            $q->min_salary = $obje->fieldCheck->min_salary;
        }
        if(isset($obje->fieldCheck->employment_type)){
            $q->employment_type = $obje->fieldCheck->employment_type;
        }
        if(isset($obje->fieldCheck->job_position_level)){
            $q->job_position_level = $obje->fieldCheck->job_position_level;
        }
        if(isset($obje->fieldCheck->education_level)){
            $q->education_level = $obje->fieldCheck->education_level;
        }
        if(isset($obje->fieldCheck->field_of_study)){
            $q->field_of_study = $obje->fieldCheck->field_of_study;
        }
        if(isset($obje->fieldCheck->experience)){
            $q->experience = $obje->fieldCheck->experience;
        }
        if(isset($obje->fieldCheck->skill)){
            $q->skill = $obje->fieldCheck->skill;
        }
        if(isset($obje->fieldCheck->language)){
            $q->language = $obje->fieldCheck->language;
        }
        if(isset($obje->fieldCheck->currency)){
            $q->currency = $obje->fieldCheck->currency;
        }
        $q->save();
        return response()->json([
            'status'=>'success',
            'message'=> 'Updated!'
        ],200); 
        
    }
    public function saveSkill($q,$obje,$request){
        $request->request->add(['skill_name' => $obje->fieldCheck->skill_name]);
        $validator = Validator::make($request->all(), [
            'skill_name' => 'required'
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $yy = $request->skill_name;
        $q = $q->whereIn('skill_name',$yy)->get();
        if(count($q)>0){
            for($p=0;$p<count($yy);$p++){
                $pp = new Skill();
                $isEquals = false;
                for($a=0;$a<count($q);$a++){
                    if(strcasecmp($q[$a]->skill_name,$yy[$p]) == 0){
                        $isEquals = true;
                        break;
                    }
                }
                if($isEquals == false){
                    $pp->skill_name = $yy[$p];
                    $pp->save();
                }
            }
        }else{
            for($p=0;$p<count($yy);$p++){
                $pp = new Skill();
                $pp->skill_name = $yy[$p];
                $pp->save();
            }
        }
        return response()->json([
            'status'=>'success',
            'message'=> 'Skill saved!'
        ],200); 
    }
    public function whereSkillFilter($q,$obje,$request){
        
        return $q = $q->where('skill_name','like','%'.$obje->fieldCheck->skill_name.'%');
    }
    public function saveNewPostJobInfo($q,$obje,$request){
        $request->request->add(['job_name' => $obje->fieldCheck->job_name]);
        $request->request->add(['job_description' => $obje->fieldCheck->job_description]);
        $request->request->add(['category_id' => $obje->fieldCheck->category_id]);
        $request->request->add(['location' => $obje->fieldCheck->location]);
        $request->request->add(['max_salary' => $obje->fieldCheck->max_salary]);
        $request->request->add(['min_salary' => $obje->fieldCheck->min_salary]);
        $request->request->add(['employment_type' => $obje->fieldCheck->employment_type]);
        $request->request->add(['job_position_level' => $obje->fieldCheck->job_position_level]);
        $request->request->add(['education_level'=> $obje->fieldCheck->education_level]);
        $request->request->add(['field_of_study'=> $obje->fieldCheck->field_of_study]);
        $request->request->add(['experience'=> $obje->fieldCheck->experience]);
        $request->request->add(['skill'=> $obje->fieldCheck->skill]);
        $request->request->add(['language'=> $obje->fieldCheck->language]);
        $request->request->add(['currency'=> $obje->fieldCheck->currency]);

        $validator = Validator::make($request->all(), [
            'job_name' => 'required',
            'job_description'     => 'required',
            'category_id'     => 'required',
            'location' => 'required',
            'max_salary' => 'required|numeric',
            'min_salary' => 'required|numeric'
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }
        $q = new PostJobInfo();
        $auth = \Auth::guard('member');
        if($auth == null){
            return response()->json([
                'status'=>'error',
                'message'=> 'User No yet u'
            ],500); 
        }
        $q->user_company_id = $auth->user()->id;
        $q->job_name = $request->job_name;
        $q->job_description = $request->job_description;
        $q->category_id = $request->category_id;
        $q->max_salary = $request->max_salary;
        $q->min_salary = $request->min_salary;
        $q->location = $request->location;
        $q->employment_type = $request->employment_type;
        $q->job_position_level = $request->job_position_level;
        $q->education_level = $request->education_level;
        $q->field_of_study = $request->field_of_study;
        $q->experience = $request->experience;
        $q->currency = $request->currency;
        $q->skill = $request->skill;
        $q->language = $request->language;
        $q->save();
        $q = PostJobInfo::where('id','=',$q->id)->with('fromJobPositionCategory')->with('fromUserCompany')->first();
        return response()->json([
            'status'=>'success',
            'message'=> [
                'id' => $q->id,
                'fromJobPositionCategory'=>$q->fromJobPositionCategory,
                'fromUserCompany' => $q->fromUserCompany
            ]
        ],200); 
    }
    public function getWithJsonWhere($q){
        // return $q->whereRaw('json_contains(location, "[7]")');
        // return $q->whereRaw('json_contains(location->"$[*].country" ,json_array(1))');
        // return $q->whereRaw('json_contains(location->"$[*].city" ,json_array(6))')->orWhereRaw('json_contains(location->"$[*].city" ,json_array(3))');
        // return $q->whereRaw('json_contains(location->"$[*].city" ,json_array(6))')->orWhereRaw('json_contains(location->"$[*].city" ,json_array(3))');
    }
    public function getCurrentUserAboutInfo($q){
        $auth = \Auth::guard('member');
        return $q->where('member_id','=',$auth->user()->id);
    }
    public function translateDateEndExp($value){
    	return $value=='0000-00-00'?null:$value;
    }
    // bisa digunakan User dan company
    public function changePassword($q,$obje,$request){
        // check requirement
        $result = $this->getRequireFilter($obje,['getCurrentUserId']);
        if($result->rejected()){
            return response()->json($result->message(),500);
        }
        // echo json_encode($obje->fieldCheck->current_password);
        $request->request->add(['current_password' => $obje->fieldCheck->current_password]);
        $request->request->add(['new_password' => $obje->fieldCheck->new_password]);
        $request->request->add(['confirm_password' => $obje->fieldCheck->confirm_password]);
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password'     => 'required',
            'confirm_password' => 'required|same:new_password'
        ]);
        if($validator->fails()){
            return response()->json([
                'status'=>'error',
                'message'=> $validator->errors()
            ],500);
        }

        $current_password = null;
        if(isset(\Auth::guard('member')->user()->password)){
            $current_password = \Auth::guard('member')->user()->password;

        }

        if(Hash::check($request->get('current_password'), $current_password)){   
            $q = $q->first();
            $q->password = bcrypt($request->get('new_password'));
            $q->save();
        }else{
            // dont next process;
            return response()->json([
                'status'=>'error',
                'message'=> 'Is Not Your Current Password!'
            ],500);
        }
        // dont next process;
        return response()->json([
            'status'=>'success',
            'message'=> $q
        ],201);
        // echo json_encode($obje->fieldCheck->current_password);
    }

    public function getAuthUserCompanyId($q){
        $auth = \Auth::guard('member');
        return $q->where('id','=',$auth->user()->id);
    }

    public function getCurrentCompanyAboutInfo($q){
        $auth = \Auth::guard('member');
        return $q->where('user_company_id','=',$auth->user()->id);
    }
    public function getCurrentCompanyId($q){
        $auth = \Auth::guard('member');
        return $q->where('user_company_id','=',$auth->user()->id);
    }

    public function getCoverPhotoCompany($q){
        $auth = \Auth::guard('member');
        $companyBoutInfo = new CompanyAboutInfo();
        $gg = CompanyAboutInfo::where('user_company_id','=',$auth->user()->id)->first();
        return $q->where('table_name','=',$companyBoutInfo->table)
            ->where('table_id','=',$gg->id)
            ->where('field_name','=',CompanyAboutInfo::$header_field);
    }

    public function getProfilePhotoCompany($q){
        $auth = \Auth::guard('company');
        $companyBoutInfo = new CompanyAboutInfo();
        $gg = CompanyAboutInfo::where('user_company_id','=',$auth->user()->id)->first();
        return $q->where('table_name','=',$companyBoutInfo->table)
            ->where('table_id','=',$gg->id)
            ->where('field_name','=',CompanyAboutInfo::$profile_field);
    }


}
