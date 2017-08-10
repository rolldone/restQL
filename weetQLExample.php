<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserAboutInfo;
use App\UserExpInfo;
use App\UserEduInfo;
use App\UserSkillInfo;
use App\UserLangInfo;
use App\CompanyAboutInfo;
use App\Image;
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
                'message'=>'Successfully!'//$auth->user()
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
        return $q->where('member_id','=',$auth->user()->id);
    }

    // get current id for User model
    public function getCurrentUserId($q){
    	$auth = \Auth::guard('member');
        return $q->where('id','=',$auth->user()->id);
    }

    public function getCoverPhotoUser($q){
        $auth = \Auth::guard('member');
        $UserAboutInfo = new UserAboutInfo();
        $gg = UserAboutInfo::where('member_id','=',$auth->user()->id)->first();
        return $q->where('table_name','=',$UserAboutInfo->table)
            ->where('table_id','=',$gg->id)
            ->where('field_name','=',CompanyAboutInfo::$header_field);
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
        if(isset($obje->fieldCheck->current_job)){
            $q->current_job = $obje->fieldCheck->current_job;
        }
        if(isset($obje->fieldCheck->graduate)){
            $q->graduate = $obje->fieldCheck->graduate;
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
    public function saveCompanyAboutInfo($q,$obje,$request){
        $q = $q->first();
        if(isset($obje->fieldCheck->about_company)){
            $q->about_company = $obje->fieldCheck->about_company;
        }
        if(isset($obje->fieldCheck->why_join_us)){
            $q->why_join_us = $obje->fieldCheck->why_join_us;
        }
        if(isset($obje->fieldCheck->location_from)){
            $q->location_from = $obje->fieldCheck->location_from;
        }
        if(isset($obje->fieldCheck->company_name)){
            $q->company_name = $obje->fieldCheck->company_name;
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
    
    public function getCurrentUserAboutInfo($q){
        $auth = \Auth::guard('member');
        return $q->where('member_id','=',$auth->user()->id);
    }
    public function translateDateEndExp($value){
    	return $value=='0000-00-00'?null:$value;
    }
    // bisa digunakan User dan company
    public function changePassword($q,$obje,$request){
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
        if($current_password == null){
            if(isset(\Auth::guard('company')->user()->password)){
                $current_password = \Auth::guard('company')->user()->password;    
            }
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
        $auth = \Auth::guard('company');
        return $q->where('id','=',$auth->user()->id);
    }

    public function getCurrentCompanyAboutInfo($q){
        $auth = \Auth::guard('company');
        return $q->where('user_company_id','=',$auth->user()->id);
    }
    public function getCurrentCompanyId($q){
        $auth = \Auth::guard('company');
        return $q->where('user_company_id','=',$auth->user()->id);
    }

    public function getCoverPhotoCompany($q){
        $auth = \Auth::guard('company');
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
