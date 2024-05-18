<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App;



use App\Services\UserDataService;


class CompanyController extends Controller{
    public function __construct()
    {
    }
    

    public function add_company(Request $request)
    {
        $date = date('Y-m-d H:i:s');
        $data = array(
            'company_name' => $request->company_name,
            'business_entity_type' => $request->business_entity,
            'business_address' => $request->business_address,
            'mailing_address' => $request->mailing_address,
            'business_phone_number' => $request->bus_contact_number,
            'email_address'=>$request->email,
            'website' => $request->website,
            'full_name' => $request->full_name,
            'title' => $request->title,
            'primary_email' => $request->primary_email,
            'primary_phone_number' => $request->primary_contact_number,
            'business_nature' => $request->business_nature,
            'industry_sector' => $request->industry_sector,
            'revenue' => $request->revenue,
            'employees' => $request->employees,
            );
            $aid= DB::table('company')->insertGetId($data);
         

        if ($aid) { 
                $data = array('status' => true, 'msg' => 'Company added successfully');
                return response()->json($data);

        } else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }

    }
    public function get_companies(REQUEST $request){
        $query = DB::table('company as c')
        ->where('c.deleted', '=', 0)
        ->select(
            'c.*', DB::raw('c.image as avatar')  
        )
        ->orderBy('c.created_at', 'DESC');
        $company_info = $query->get();
        $data = array('status' => true, 'data' => $company_info);
        return response()->json($data);
    }
        
        

    public function get_single_company(REQUEST $request){
        $company_info = DB::table('company as c')
        ->where('c.deleted', '=', 0)
        ->where('c.id','=',$request->id)
        ->select(
            'c.*', DB::raw('c.image as avatar')
        )
        ->orderBy('c.created_at', 'DESC')
        ->first();
    
        $data = array('status' => true, 'data' => $company_info);
        return response()->json($data);
    }
    
    public function update_company(REQUEST $request){
        $image=null;
        if ($request->hasFile('image')) {
            $image = $request->file('image')->store('images', 'public');
            $image = 'storage/app/public/images/'.$image;
        }
      
        $update_data=DB::table('company')
        ->where('id','=',$request->id)
        ->update([
            'company_name' => $request->company_name,
            'business_entity_type' => $request->business_entity_type,
            'business_address' => $request->business_address,
            'mailing_address' => $request->mailing_address,
            'business_phone_number' => $request->business_phone_number,
            'email_address'=>$request->email_address,
            'website' => $request->website,
            'full_name' => $request->full_name,
            'title' => $request->title,
            'primary_email' => $request->primary_email,
            'primary_phone_number' => $request->primary_phone_number,
            'business_nature' => $request->business_nature,
            'industry_sector' => $request->industry_sector,
            'revenue' => $request->revenue,
            'employees' => $request->employees,
            'image'=>$image
        ]);
        
        if($update_data){
            $data = array('status' => true, 'msg' => 'Company details updated successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }
    public function delete_company(REQUEST $request){
        $deleted_info=DB::table('company')
        ->where('id','=',$request->id)
        ->update([
            'deleted'=>1,
        ]);
        if($deleted_info){
            $data = array('status' => true, 'msg' => 'Company deleted successfully');
            return response()->json($data);
            } 
        else {
            // return true;
            $data = array('status' => false, 'msg' => 'Failed');
            return response()->json($data);
        }
    }


}