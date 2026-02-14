<?php

namespace App\Http\Controllers;

use App\Models\LeadInformation;
use App\Models\LogLead;
use App\Models\LogSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\Types\This;

class LogController extends Controller
{
    static public function AuditLog($action, $entity, $id, $pre_data, $post_data, $description, Request $request){


        $log = new LogSystem();
        $log->action    =   $action;
        $log->url_request   =   $request->url();
        $log->entity_name   =   $entity;
        $log->entity_id =   $id;
        $log->description   =   $description;
        $log->pre_data  =   json_encode($pre_data);
        $log->post_data =   json_encode($post_data);
        $log->host_address  =  $request->ip();
        $log->host_name =  $request->header('User-Agent'); ;
        $log->created_by    =    Auth::id();
        $log->created_by_name   =   Auth::user()->name;

        $log->save();
    }


    static public function AuditLogUsers($action, $entity, $id, $pre_data, $post_data, $description, Request $request, $user){


        $log = new LogSystem();
        $log->action    =   $action;
        $log->url_request   =   $request->url();
        $log->entity_name   =   $entity;
        $log->entity_id =   $id;
        $log->description   =   $description;
        $log->pre_data  =   json_encode($pre_data);
        $log->post_data =   json_encode($post_data);
        $log->host_address  =  $request->ip();
        $log->host_name =  $request->header('User-Agent'); ;
        $log->created_by    =    $user->id;
        $log->created_by_name   =   $user->name;

        $log->save();
    }

    static public function AuditLogRegister($action, $entity, $id, $pre_data, $post_data, $description, Request $request){


        $log = new LogSystem();
        $log->action    =   $action;
        $log->url_request   =   $request->url();
        $log->entity_name   =   $entity;
        $log->entity_id =   $id;
        $log->description   =   $description;
        $log->pre_data  =   json_encode($pre_data);
        $log->post_data =   json_encode($post_data);
        $log->host_address  =  $request->ip();
        $log->host_name =  $request->header('User-Agent'); ;
        $log->created_by    =    $post_data->id;
        $log->created_by_name   =   $post_data->name;

        $log->save();
    }
}
