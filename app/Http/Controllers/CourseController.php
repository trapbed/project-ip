<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use App\Models\Lesson;

use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $header = 'Все курсы';
        $all_access_courses = DB::table('courses')->select( 'courses.id',
        'categories.title as category',
        'courses.title',
        'courses.description',
        'courses.image',
        'users.name as author',
        'courses.student_count',
        'courses.test',
        'courses.created_at',
        'courses.access',
        DB::raw('COUNT(lessons.id) as lesson_count'))->where('access','=', '1');

        
        if($request->search){
            $header = "Поиск '".$request->search."'";
            $old_search = $request->search;
            $all_access_courses = $all_access_courses->where('courses.title','LIKE', '%'.$request->search.'%');
        }
        if($request->category){
            $name_cat = Category::select('title')->where('id', '=', $request->category)->get()[0]->title;
            $header = "Курсы по категории '".$name_cat."'";
            $old_cat = $request->category;
            $category = $request->category;
            $all_access_courses = $all_access_courses->where('categories.id', '=', $category);
        }
        if(Auth::check()== true && Auth::user()->role == 'author'){
            $all_access_courses = $all_access_courses->where('author', '=', Auth::user()->id);
        }

        if($request->category && $request->search){
            $header = "Курсы по категории '".$name_cat."' с поиском '".$request->search."'";
        }
        $all_access_courses = $all_access_courses->join('categories', 'categories.id', '=', 'courses.category')
                                                ->join('users', 'users.id', '=', 'courses.author')
                                                ->leftJoin('lessons', 'lessons.course_id', '=', 'courses.id')->groupBy('courses.id');
        
        $order_by = $request->order;

        if($request->order != 'access DESC' && $request->order!=null){
            $old_order = $request->order;
            $order_by = explode(" ", $order_by);
            $all_access_courses = $all_access_courses->orderBy($order_by[0], $order_by[1]);
        }
        else{
            $all_access_courses = $all_access_courses->orderBy('student_count', 'DESC');
        }
        
        $all_access_courses = $all_access_courses->get();

        $categories = Category::select('id', 'title')->where('exist', '=', '1')->get();
        if(Auth::check()==true && Auth::user()->role == 'author'){
            return view('author/courses', ['courses'=> $all_access_courses, 'categories'=>$categories, 'count_courses'=>$all_access_courses->count(), 'old_search'=>$old_search, "old_cat"=>$old_cat, 'old_order'=>$old_order, 'header'=>$header]);
        }
        else{
            return view('courses', ['courses'=> $all_access_courses, 'categories'=>$categories, 'count_courses'=>$all_access_courses->count(), 'old_search'=>$old_search, "old_cat"=>$old_cat, 'old_order'=>$old_order, 'header'=>$header]);
        }
        
    }
    public function one_course_main($id_course){
        $info_course = Course::select('courses.id', 'courses.title', 'categories.title as category','description','image','users.name as author','student_count', 'test')->where('courses.id','=', $id_course)
        ->join('users', 'users.id', '=', 'courses.author')
        ->join('categories', 'categories.id', '=', 'courses.category');

        if($info_course->exists() == true){
            $info_course= $info_course->get()[0];
            return view('one_course', ['title'=>$info_course->title, 'course'=>$info_course, 'id'=>$id_course]);
        }
        else{
            return redirect()->route('courses');
        }
    }
    public function get_all_admin(){
        $courses = Course::select('courses.id as course_id','courses.title as course_title','categories.title as category_title', 'description', 'users.name as author','access','test', 'courses.created_at')
        ->JOIN('users','users.id','=', 'courses.author')
        ->JOIN('categories','categories.id','=','courses.category')->get();
        // dd(count($courses));
        return view('admin/courses', ['courses'=>$courses]);
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

    function update_course($id){
        $categories = Category::select('id', 'title')->get();
        $course = Course::select('id','category','title','description','image')->where('id','=', $id)->get()[0];
        return view('author/update_course', ['categories'=>$categories, 'course'=>$course]);
    }
    
    public function author_more_info_course($id){
        $course = Course::select('courses.id','courses.title', 'description', 'student_count', 'categories.title as category')->join('categories', 'courses.category', '=', 'categories.id')->where('courses.id', '=', $id)->get()[0];
        $lessons = Lesson::select('*')->where('course_id', '=', $id)->get();
        // dd($lessons);
        $count_lessons = $lessons->count();
        return view('author/one_course', ['course'=>$course, 'lessons'=>$lessons, 'count_lessons'=>$count_lessons]);
    }
// AUTHOR
    public function data_for_create_course($id){
        $course = Course::select('courses.*', DB::raw('COUNT(lessons.id) as lesson_count'))->where('courses.id', '=', $id)->leftJoin('lessons', 'lessons.course_id', '=', 'courses.id')->groupBy('courses.id')->get()[0];
        return view('author/create_lesson', ['course'=>$course]);
    }
    
}
