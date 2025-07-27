import { JobResponse, JobCategory, District, ApiResponse } from '../types/Job';

class ApiServiceClass {
  private baseUrl: string;
  private apiKey: string;

  constructor() {
    this.baseUrl = process.env.REACT_APP_API_URL || 'https://your-api-url.com/api';
    this.apiKey = process.env.REACT_APP_API_KEY || '';
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;
    const config: RequestInit = {
      headers: {
        'Content-Type': 'application/json',
        ...(this.apiKey && { Authorization: `Bearer ${this.apiKey}` }),
        ...options.headers,
      },
      ...options,
    };

    try {
      const response = await fetch(url, config);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  async getJobs(params: Record<string, any> = {}): Promise<ApiResponse<JobResponse>> {
    // Remove null/undefined values
    const cleanParams = Object.entries(params)
      .filter(([_, value]) => value !== null && value !== undefined && value !== '')
      .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {});

    const queryString = new URLSearchParams(cleanParams).toString();
    const endpoint = `/jobs${queryString ? `?${queryString}` : ''}`;
    
    return this.request<JobResponse>(endpoint);
  }

  async getJobCategories(): Promise<ApiResponse<JobCategory[]>> {
    return this.request<JobCategory[]>('/job-categories');
  }

  async getDistricts(): Promise<ApiResponse<District[]>> {
    return this.request<District[]>('/districts');
  }

  async getJobById(id: number): Promise<ApiResponse<Job>> {
    return this.request<Job>(`/jobs/${id}`);
  }

  async searchJobs(query: string): Promise<ApiResponse<JobResponse>> {
    return this.getJobs({ search: query });
  }

  // Add other API methods as needed
  async applyToJob(jobId: number, applicationData: any): Promise<ApiResponse<any>> {
    return this.request<any>(`/jobs/${jobId}/apply`, {
      method: 'POST',
      body: JSON.stringify(applicationData),
    });
  }

  async bookmarkJob(jobId: number): Promise<ApiResponse<any>> {
    return this.request<any>(`/jobs/${jobId}/bookmark`, {
      method: 'POST',
    });
  }

  async unbookmarkJob(jobId: number): Promise<ApiResponse<any>> {
    return this.request<any>(`/jobs/${jobId}/bookmark`, {
      method: 'DELETE',
    });
  }
}

export const ApiService = new ApiServiceClass();
