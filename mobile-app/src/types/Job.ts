export interface Job {
  id: number;
  title: string;
  company_name?: string;
  company_logo?: string;
  category_id?: number;
  category_text?: string;
  district_id?: number;
  district_text?: string;
  address?: string;
  employment_status?: string;
  workplace?: string;
  minimum_salary?: number;
  maximum_salary?: number;
  deadline: string;
  created_at: string;
  updated_at: string;
  status: string;
  posted_by_id?: number;
  industry?: string;
  gender?: string;
  experience_field?: string;
  experience_period?: string;
  minimum_academic_qualification?: string;
  required_video_cv?: boolean;
  vacancies_count?: number;
  min_age?: number;
  max_age?: number;
  job_level?: string;
  required_skills?: string[];
  description?: string;
  requirements?: string;
  benefits?: string;
}

export interface JobFilters {
  category?: number | null;
  district?: number | null;
  employment_status?: string | null;
  workplace?: string | null;
  salary_min?: number | null;
  salary_max?: number | null;
  deadline_from?: string | null;
  deadline_to?: string | null;
  company_id?: number | null;
  experience_level?: string | null;
  job_level?: string | null;
  required_video_cv?: boolean | null;
  min_age?: number | null;
  max_age?: number | null;
  required_skills?: string[];
  education_level?: string | null;
  sort_by?: string;
  sort_order?: string;
  industry?: string | null;
  gender?: string | null;
  experience_field?: string | null;
}

export interface JobCategory {
  id: number;
  name: string;
  description?: string;
  slug?: string;
  category_type?: string;
  status?: string;
}

export interface District {
  id: number;
  name: string;
  region?: string;
  country?: string;
}

export interface JobResponse {
  data: Job[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ApiResponse<T> {
  data: T;
  message: string;
  status: number;
}
