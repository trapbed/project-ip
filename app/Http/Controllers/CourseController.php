<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;


class CourseController extends Controller
{
    //main
    public function main(){
        $newest_course = Course::select('courses.id','categories.title as category', 'courses.title','description')->where('access', '=', '1')->join('categories', 'categories.id', '=', 'courses.category')->orderBy('student_count', 'DESC')->limit(5)->get();
        // dd($newest_course);
        return view('main', ['courses'=>$newest_course]);
    }
// courses
    public function main_courses(Request $request){
        $old_search = "";
        $old_cat = "";
        $old_order = "";
        // dump($request);
        $all_access_courses = DB::table('courses')->select('courses.id','categories.title as category','courses.title','description', 'users.name as author', 'student_count', 'test')->where('access','=', '1');
        // if($search || $category || $order){
        //     dump($search, $category, $order);
        // }
        
        if($request->search){
            $old_search = $request->search;
            // dump($category);
            $all_access_courses = $all_access_courses->where('courses.title','LIKE', '%'.$request->search.'%');
        }
        if($request->category){
            // dump($request);
            $old_cat = $request->category;
            $category = $request->category;
            $all_access_courses = $all_access_courses->where('categories.id', '=', $category);
        }
        $all_access_courses = $all_access_courses->join('categories', 'categories.id', '=', 'courses.category')->join('users', 'users.id', '=', 'courses.author');
        
        $abc = "no";
        $order_by = $request->order;

        if($request->order != 'access DESC' && $request->order!=null){
            $old_order = $request->order;
            $abc = "yes";
            $order_by = explode(" ", $order_by);
            $all_access_courses = $all_access_courses->orderBy($order_by[0], $order_by[1]);
        }
        else{
            $all_access_courses = $all_access_courses->orderBy('student_count', 'DESC');
        }
        
        // dd(explode(" ", $order_by));
        $all_access_courses = $all_access_courses->get();

        $categories = Category::select('id', 'title')->where('exist', '=', '1')->get();
        
        return view('courses', ['courses'=> $all_access_courses, 'categories'=>$categories, 'old_search'=>$old_search, "old_cat"=>$old_cat, 'old_order'=>$old_order]);
        
    }
    public function get_all_admin(){
        $courses = Course::select('courses.id as course_id','courses.title as course_title','categories.title as category_title', 'description', 'users.name as author','access','test', 'courses.created_at')
        ->JOIN('users','users.id','=', 'courses.author')
        ->JOIN('categories','categories.id','=','courses.category')->get();
        // dd(count($courses));
        return view('admin/courses', ['courses'=>$courses])->withInputs();
    }

    public function change_access_course($access, $id_course){
        // dd($access, $id_course);
        $update = Course::where('id', '=', $id_course)->update(['access'=>$access]);
        $courses = Course::select('courses.id as course_id','courses.title as course_title','categories.title as category_title', 'description', 'users.name as author','access','test', 'courses.created_at')
        ->JOIN('users','users.id','=', 'courses.author')
        ->JOIN('categories','categories.id','=','courses.category')->get();
        if($update){
            return back()->with(['mess'=>'Доступ изменен!', 'courses'=>$courses]);
        }
        else{
            return back()->with(['mess'=>'Не удалось изменить доступ!', 'courses'=>$courses]);
        }
        // return response()->json($request);
    }
}