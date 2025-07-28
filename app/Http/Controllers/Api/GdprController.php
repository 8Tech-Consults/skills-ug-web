<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GdprConsent;
use App\Models\GdprRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GdprController extends Controller
{
    /**
     * Get user's consent status for all consent types.
     */
    public function getConsents(Request $request)
    {
        try {
            $userId = $request->get('logged_in_user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User ID is required',
                    'data' => null
                ]);
            }
            
            $consents = GdprConsent::where('user_id', $userId)
                ->select('consent_type', 'consented', 'version', 'consented_at')
                ->get()
                ->keyBy('consent_type');

            // Define all consent types with descriptions
            $consentTypes = [
                'data_processing' => [
                    'title' => 'Essential Data Processing',
                    'description' => 'Allow us to process your personal data for core functionality including account management, profile creation, job applications, service bookings, course enrollments, and communication features.',
                    'required' => true,
                ],
                'marketing' => [
                    'title' => 'Marketing Communications',
                    'description' => 'Receive promotional emails about new job opportunities, featured services, course announcements, platform updates, and special offers.',
                    'required' => false,
                ],
                'analytics' => [
                    'title' => 'Analytics & Performance Tracking',
                    'description' => 'Allow us to collect anonymous usage data to improve our platform, including app performance metrics and feature usage patterns.',
                    'required' => false,
                ],
                'cookies' => [
                    'title' => 'Enhanced Cookies & Personalization',
                    'description' => 'Store cookies for personalized content recommendations, remembering your preferences, and providing targeted job suggestions.',
                    'required' => false,
                ],
                'profile_visibility' => [
                    'title' => 'Profile Visibility to Employers',
                    'description' => 'Allow employers and recruiters to discover your profile in our CV Bank and contact you about job opportunities.',
                    'required' => false,
                ],
                'location_services' => [
                    'title' => 'Location-Based Services',
                    'description' => 'Use your location to show nearby job opportunities, local service providers, and region-specific content.',
                    'required' => false,
                ],
                'communication_tracking' => [
                    'title' => 'Communication Analytics',
                    'description' => 'Analyze communication patterns to improve our messaging system and provide better matching between users.',
                    'required' => false,
                ],
                'third_party_integrations' => [
                    'title' => 'Third-Party Service Integrations',
                    'description' => 'Share necessary data with payment processors, cloud storage providers, and other GDPR-compliant partners.',
                    'required' => false,
                ],
            ];

            $response = [];
            foreach ($consentTypes as $type => $info) {
                $consent = $consents->get($type);
                $response[] = [
                    'consent_type' => $type,
                    'title' => $info['title'],
                    'description' => $info['description'],
                    'required' => $info['required'],
                    'consented' => $consent ? $consent->consented : false,
                    'version' => $consent ? $consent->version : null,
                    'consented_at' => $consent ? $consent->consented_at : null,
                ];
            }

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching consents: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Update user consent for a specific type.
     */
    public function updateConsent(Request $request)
    {
        try {
            $userId = $request->get('logged_in_user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User ID is required',
                    'data' => null
                ]);
            }

            $validator = Validator::make($request->all(), [
                'consent_type' => 'required|string|in:data_processing,marketing,analytics,cookies,profile_visibility,location_services,communication_tracking,third_party_integrations',
                'consented' => 'required|in:true,false,1,0,"true","false","1","0"',
                'consent_text' => 'required|string|min:10',
                'version' => 'nullable|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $consentType = $request->consent_type;
            
            // Convert consented value to boolean - handle various formats from mobile apps
            $consentedRaw = $request->consented;
            $consented = in_array($consentedRaw, [true, 1, '1', 'true', 'TRUE'], true);

            // Check if it's a required consent and user is trying to revoke it
            if ($consentType === 'data_processing' && !$consented) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Data processing consent is required for using our services',
                    'data' => null
                ], 400);
            }

            if ($consented) {
                // Record consent
                GdprConsent::recordConsent(
                    $userId,
                    $consentType,
                    $request->consent_text,
                    $request->get('version', '1.0'),
                    $request->ip(),
                    $request->userAgent()
                );
            } else {
                // Revoke consent
                GdprConsent::revokeConsent($userId, $consentType);
            }

            return response()->json([
                'code' => 1,
                'message' => $consented ? 'Consent recorded successfully' : 'Consent revoked successfully',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error updating consent: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's GDPR requests.
     */
    public function getRequests(Request $request)
    {
        try {
            $userId = $request->get('logged_in_user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User ID is required',
                    'data' => null
                ]);
            }
            
            $requests = GdprRequest::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'request_type' => $request->request_type,
                        'request_type_label' => $request->getRequestTypeLabel(),
                        'status' => $request->status,
                        'status_label' => $request->getStatusLabel(),
                        'status_color' => $request->getStatusColor(),
                        'reason' => $request->reason,
                        'admin_notes' => $request->admin_notes,
                        'requested_at' => $request->requested_at,
                        'processed_at' => $request->processed_at,
                        'completed_at' => $request->completed_at,
                        'data_file_path' => $request->data_file_path,
                    ];
                });

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching requests: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Create a new GDPR request.
     */
    public function createRequest(Request $request)
    {
        try {
            $userId = $request->get('logged_in_user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User ID is required',
                    'data' => null
                ]);
            }

            $validator = Validator::make($request->all(), [
                'request_type' => 'required|string|in:export,delete,portability',
                'reason' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 400);
            }

            $requestType = $request->request_type;

            // Check if user already has a pending request of the same type
            $existingRequest = GdprRequest::where('user_id', $userId)
                ->where('request_type', $requestType)
                ->where('status', GdprRequest::STATUS_PENDING)
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'code' => 0,
                    'message' => 'You already have a pending ' . $existingRequest->getRequestTypeLabel() . ' request. Please wait for it to be processed or cancel it before creating a new one.',
                    'data' => [
                        'existing_request' => [
                            'id' => $existingRequest->id,
                            'request_type' => $existingRequest->request_type,
                            'request_type_label' => $existingRequest->getRequestTypeLabel(),
                            'status' => $existingRequest->status,
                            'status_label' => $existingRequest->getStatusLabel(),
                            'requested_at' => $existingRequest->requested_at,
                        ]
                    ]
                ], 200); // Changed from 400 to 200 since it's not really a client error
            }

            // Create the request
            $gdprRequest = GdprRequest::createRequest(
                $userId,
                $requestType,
                $request->reason,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'code' => 1,
                'message' => 'GDPR request created successfully. You will be notified when it is processed',
                'data' => [
                    'id' => $gdprRequest->id,
                    'request_type' => $gdprRequest->request_type,
                    'request_type_label' => $gdprRequest->getRequestTypeLabel(),
                    'status' => $gdprRequest->status,
                    'status_label' => $gdprRequest->getStatusLabel(),
                    'requested_at' => $gdprRequest->requested_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error creating request: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Cancel a pending GDPR request.
     */
    public function cancelRequest(Request $request, $requestId)
    {
        try {
            $userId = $request->get('logged_in_user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User ID is required',
                    'data' => null
                ]);
            }
            
            $gdprRequest = GdprRequest::where('user_id', $userId)
                ->where('id', $requestId)
                ->first();

            if (!$gdprRequest) {
                return response()->json([
                    'code' => 0,
                    'message' => 'GDPR request not found',
                    'data' => null
                ], 404);
            }

            if (!$gdprRequest->isPending()) {
                return response()->json([
                    'code' => 0,
                    'message' => 'Only pending requests can be cancelled',
                    'data' => null
                ], 400);
            }

            $gdprRequest->delete();

            return response()->json([
                'code' => 1,
                'message' => 'GDPR request cancelled successfully',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error cancelling request: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get user's data summary for transparency.
     */
    public function getDataSummary(Request $request)
    {
        try {
            $userId = $request->get('logged_in_user_id');
            
            if (!$userId) {
                return response()->json([
                    'code' => 0,
                    'message' => 'User ID is required',
                    'data' => null
                ]);
            }
            
            // Count user data across different tables
            $dataSummary = [
                'profile' => [
                    'label' => 'Profile Information',
                    'description' => 'Basic profile data, settings, and preferences',
                    'count' => 1,
                ],
                'applications' => [
                    'label' => 'Job Applications',
                    'description' => 'Applications submitted to various job postings',
                    'count' => DB::table('job_applications')->where('applicant_id', $userId)->count(),
                ],
                'subscriptions' => [
                    'label' => 'Course Subscriptions',
                    'description' => 'Courses you have subscribed to',
                    'count' => DB::table('course_subscriptions')->where('user_id', $userId)->count(),
                ],
                'services' => [
                    'label' => 'Services Provided',
                    'description' => 'Services you have offered on the platform',
                    'count' => DB::table('services')->where('provider_id', $userId)->count(),
                ],
                'gdpr_consents' => [
                    'label' => 'GDPR Consents',
                    'description' => 'Your consent history and preferences',
                    'count' => GdprConsent::where('user_id', $userId)->count(),
                ],
            ];

            $totalItems = array_sum(array_column($dataSummary, 'count'));
            $user = User::find($userId);

            return response()->json([
                'code' => 1,
                'message' => 'Success',
                'data' => [
                    'summary' => $dataSummary,
                    'total_items' => $totalItems,
                    'last_updated' => $user ? $user->updated_at : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 0,
                'message' => 'Error fetching data summary: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
