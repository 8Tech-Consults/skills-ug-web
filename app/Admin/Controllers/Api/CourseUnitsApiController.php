<?php

namespace App\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseUnit;
use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Http\Request;

class CourseUnitsApiController extends Controller
{
    /**
     * Get course units for AJAX dropdown (following dynamic pattern)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCourseUnits(Request $request)
    {
        $_model = trim($request->get('model', 'CourseUnit'));
        $conditions = [];
        
        // Extract query conditions from request parameters
        foreach ($request->all() as $key => $v) {
            if (substr($key, 0, 6) != 'query_') {
                continue;
            }
            $_key = str_replace('query_', "", $key);
            $conditions[$_key] = $v;
        }

        if (strlen($_model) < 2) {
            return response()->json([
                'data' => []
            ]);
        }

        $model = "App\\Models\\" . $_model;
        $search_by_1 = trim($request->get('search_by_1', 'title')); // Default to 'title'
        $search_by_2 = trim($request->get('search_by_2', 'description')); // Default to 'description'

        // Validate search fields
        if (empty($search_by_1)) {
            return response()->json([
                'error' => 'search_by_1 parameter is required',
                'data' => []
            ]);
        }

        $q = trim($request->get('q', ''));

        // First search query
        $query1 = $model::where($search_by_1, 'like', "%$q%");
        
        // Apply conditions
        foreach ($conditions as $key => $value) {
            $query1->where($key, $value);
        }
        
        $res_1 = $query1->with('course')->limit(20)->get();
        $res_2 = [];

        // Second search query if needed
        if ((count($res_1) < 20) && (strlen($search_by_2) > 1)) {
            $query2 = $model::where($search_by_2, 'like', "%$q%");
            
            // Apply conditions
            foreach ($conditions as $key => $value) {
                $query2->where($key, $value);
            }
            
            $res_2 = $query2->with('course')->limit(20)->get();
        }

        $data = [];
        
        // Process first result set
        foreach ($res_1 as $key => $v) {
            $name = "";
            if (isset($v->title)) {
                $name = " - " . $v->title;
            }
            
            // For CourseUnit, include course title
            $courseTitle = "";
            if (isset($v->course) && isset($v->course->title)) {
                $courseTitle = $v->course->title . " - ";
            }
            
            $data[] = [
                'id' => $v->id,
                'text' => $courseTitle . "#$v->id" . $name
            ];
        }
        
        // Process second result set
        foreach ($res_2 as $key => $v) {
            $name = "";
            if (isset($v->title)) {
                $name = " - " . $v->title;
            }
            
            // For CourseUnit, include course title
            $courseTitle = "";
            if (isset($v->course) && isset($v->course->title)) {
                $courseTitle = $v->course->title . " - ";
            }
            
            $data[] = [
                'id' => $v->id,
                'text' => $courseTitle . "#$v->id" . $name
            ];
        }

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Get courses for cascading dropdown (following dynamic pattern)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCourses(Request $request)
    {
        $_model = trim($request->get('model', 'Course'));
        $conditions = [];
        
        // Extract query conditions from request parameters
        foreach ($request->all() as $key => $v) {
            if (substr($key, 0, 6) != 'query_') {
                continue;
            }
            $_key = str_replace('query_', "", $key);
            $conditions[$_key] = $v;
        }

        if (strlen($_model) < 2) {
            return response()->json([
                'data' => []
            ]);
        }

        $model = "App\\Models\\" . $_model;
        $search_by_1 = trim($request->get('search_by_1', 'title')); // Default to 'title'
        $search_by_2 = trim($request->get('search_by_2', 'instructor_name')); // Default to 'instructor_name'

        // Validate search fields
        if (empty($search_by_1)) {
            return response()->json([
                'error' => 'search_by_1 parameter is required',
                'data' => []
            ]);
        }

        $q = trim($request->get('q', ''));

        // First search query
        $query1 = $model::where($search_by_1, 'like', "%$q%");
        
        // Apply conditions (default to active status)
        $conditions = array_merge(['status' => 'active'], $conditions);
        foreach ($conditions as $key => $value) {
            $query1->where($key, $value);
        }
        
        $res_1 = $query1->limit(20)->get();
        $res_2 = [];

        // Second search query if needed
        if ((count($res_1) < 20) && (strlen($search_by_2) > 1)) {
            $query2 = $model::where($search_by_2, 'like', "%$q%");
            
            // Apply conditions
            foreach ($conditions as $key => $value) {
                $query2->where($key, $value);
            }
            
            $res_2 = $query2->limit(20)->get();
        }

        $data = [];
        
        // Process first result set
        foreach ($res_1 as $key => $v) {
            $name = "";
            if (isset($v->title)) {
                $name = " - " . $v->title;
            }
            
            $data[] = [
                'id' => $v->id,
                'text' => "#$v->id" . $name
            ];
        }
        
        // Process second result set
        foreach ($res_2 as $key => $v) {
            $name = "";
            if (isset($v->title)) {
                $name = " - " . $v->title;
            }
            
            $data[] = [
                'id' => $v->id,
                'text' => "#$v->id" . $name
            ];
        }

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Get units by course ID
     *
     * @param Request $request
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnitsByCourse(Request $request, $courseId)
    {
        $units = CourseUnit::where('course_id', $courseId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->get();

        $results = $units->map(function ($unit) {
            return [
                'id' => $unit->id,
                'text' => $unit->title,
                'sort_order' => $unit->sort_order,
                'description' => $unit->description
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * Get next sort order for a unit
     *
     * @param Request $request
     * @param int $unitId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextSortOrder(Request $request, $unitId)
    {
        $lastMaterial = CourseMaterial::where('unit_id', $unitId)
            ->orderBy('sort_order', 'desc')
            ->first();

        $nextSortOrder = $lastMaterial ? $lastMaterial->sort_order + 1 : 1;

        return response()->json([
            'next_sort_order' => $nextSortOrder,
            'current_materials_count' => CourseMaterial::where('unit_id', $unitId)->count()
        ]);
    }

    /**
     * Get course information for a unit (for form editing)
     *
     * @param Request $request
     * @param int $unitId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnitCourse(Request $request, $unitId)
    {
        $unit = CourseUnit::with('course')->find($unitId);
        
        if (!$unit || !$unit->course) {
            return response()->json([
                'error' => 'Unit or course not found'
            ], 404);
        }

        return response()->json([
            'course_id' => $unit->course->id,
            'course_title' => $unit->course->title,
            'unit_id' => $unit->id,
            'unit_title' => $unit->title
        ]);
    }
}