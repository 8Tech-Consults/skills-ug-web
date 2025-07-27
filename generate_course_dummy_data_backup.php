<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel for database access
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * Course Dummy Data Generator
 * Based on research from Udemy, Coursera, edX, Pluralsight, and other leading platforms
 */

echo "🚀 Starting Course Module Dummy Data Generation...\n";

// Create storage directory for images
$storageImagesPath = storage_path('app/public/images/courses');
if (!is_dir($storageImagesPath)) {
    mkdir($storageImagesPath, 0755, true);
    echo "📁 Created storage directory: $storageImagesPath\n";
}

// Course Categories (Uganda-focused with practical skills)
$categories = [
    [
        'name' => 'Agriculture & Farming',
        'description' => 'Modern farming techniques, crop management, livestock, and sustainable agriculture practices for Uganda.',
        'icon' => 'fas fa-seedling',
        'color' => '#27ae60',
        'status' => 'active',
        'sort_order' => 1,
    ],
    [
        'name' => 'Carpentry & Woodworking',
        'description' => 'Learn professional carpentry skills, furniture making, and woodworking techniques using local materials.',
        'icon' => 'fas fa-hammer',
        'color' => '#8b4513',
        'status' => 'active',
        'sort_order' => 2,
    ],
    [
        'name' => 'Tailoring & Fashion Design',
        'description' => 'Master sewing, tailoring, and fashion design skills to start your own clothing business.',
        'icon' => 'fas fa-cut',
        'color' => '#e91e63',
        'status' => 'active',
        'sort_order' => 3,
    ],
    [
        'name' => 'Small Business & Entrepreneurship',
        'description' => 'Start and grow small businesses in Uganda, including market analysis and financial management.',
        'icon' => 'fas fa-store',
        'color' => '#ff9800',
        'status' => 'active',
        'sort_order' => 4,
    ],
    [
        'name' => 'Technology & Computer Skills',
        'description' => 'Basic to advanced computer skills, web development, and digital literacy for the modern workplace.',
        'icon' => 'fas fa-laptop',
        'color' => '#2196f3',
        'status' => 'active',
        'sort_order' => 5,
    ],
    [
        'name' => 'Construction & Building',
        'description' => 'Learn construction techniques, masonry, plumbing, and electrical work for the building industry.',
        'icon' => 'fas fa-hard-hat',
        'color' => '#ff5722',
        'status' => 'active',
        'sort_order' => 6,
    ],
    [
        'name' => 'Food Processing & Catering',
        'description' => 'Food preparation, preservation, catering services, and restaurant management skills.',
        'icon' => 'fas fa-utensils',
        'color' => '#4caf50',
        'status' => 'active',
        'sort_order' => 7,
    ],
    [
        'name' => 'Motor Vehicle Mechanics',
        'description' => 'Automotive repair, motorcycle maintenance, and vehicle servicing skills for the transport sector.',
        'icon' => 'fas fa-wrench',
        'color' => '#607d8b',
        'status' => 'active',
        'sort_order' => 8,
    ],
    [
        'name' => 'Financial Literacy & Savings',
        'description' => 'Personal finance, savings groups (VSLAs), microfinance, and investment strategies for Ugandans.',
        'icon' => 'fas fa-piggy-bank',
        'color' => '#9c27b0',
        'status' => 'active',
        'sort_order' => 9,
    ],
    [
        'name' => 'Languages & Communication',
        'description' => 'English proficiency, local languages, and communication skills for personal and professional growth.',
        'icon' => 'fas fa-comments',
        'color' => '#673ab7',
        'status' => 'active',
        'sort_order' => 10,
    ],
];

// Insert categories
echo "📚 Inserting course categories...\n";
DB::table('course_categories')->truncate();
foreach ($categories as $category) {
    $category['created_at'] = now();
    $category['updated_at'] = now();
    DB::table('course_categories')->insert($category);
}
echo "✅ Inserted " . count($categories) . " course categories\n";

// Course data (Uganda-focused realistic courses)
$courses = [
    // Agriculture & Farming (Category 1)
    [
        'category_id' => 1,
        'title' => 'Complete Coffee Farming and Processing Masterclass',
        'slug' => 'complete-coffee-farming-processing-masterclass',
        'description' => 'Learn coffee cultivation, harvesting, processing, and marketing to export quality from your farm in Uganda.',
        'detailed_description' => 'This comprehensive course covers everything from coffee seedling preparation to export-ready processing. Learn modern farming techniques, pest management, harvesting methods, processing for quality, and connecting with international buyers. Perfect for aspiring coffee farmers and those looking to improve their current operations.',
        'instructor_name' => 'John Mukasa',
        'instructor_bio' => 'Coffee farming expert with 15+ years experience. Former agricultural extension officer and coffee cooperative leader.',
        'instructor_avatar' => 'instructors/john-mukasa.jpg',
        'cover_image' => 'courses/coffee-farming.jpg',
        'preview_video' => 'previews/coffee-preview.mp4',
        'price' => 450000,
        'currency' => 'UGX',
        'duration_hours' => 40,
        'difficulty_level' => 'Beginner',
        'language' => 'English',
        'requirements' => json_encode(['Basic farming knowledge', 'Access to land', 'Willingness to learn']),
        'what_you_learn' => json_encode([
            'Coffee variety selection and planting',
            'Organic farming practices',
            'Harvesting at optimal ripeness',
            'Processing methods for quality beans',
            'Marketing and finding buyers',
            'Financial management for coffee farming'
        ]),
        'tags' => json_encode(['Coffee', 'Agriculture', 'Export', 'Organic Farming', 'Business']),
        'status' => 'active',
        'featured' => 'yes',
        'rating_average' => 4.8,
        'rating_count' => 1247,
        'enrollment_count' => 3890,
    ],
    // Carpentry & Woodworking (Category 2)
    [
        'category_id' => 2,
        'title' => 'Professional Furniture Making with Local Woods',
        'slug' => 'professional-furniture-making-local-woods',
        'description' => 'Master carpentry skills using Ugandan hardwoods to create beautiful, durable furniture for local and international markets.',
        'detailed_description' => 'Learn professional woodworking techniques using locally available woods like mahogany, teak, and mukwa. This course covers tool selection, wood preparation, joinery techniques, finishing, and business aspects of furniture making.',
        'instructor_name' => 'Robert Ssemakula',
        'instructor_bio' => 'Master carpenter with 20+ years experience. Owner of successful furniture workshop in Kampala.',
        'instructor_avatar' => 'instructors/robert-ssemakula.jpg',
        'cover_image' => 'courses/furniture-making.jpg',
        'preview_video' => 'previews/carpentry-preview.mp4',
        'price' => 380000,
        'currency' => 'UGX',
        'duration_hours' => 50,
        'difficulty_level' => 'Intermediate',
        'language' => 'English',
        'requirements' => json_encode(['Basic tool handling', 'Physical fitness', 'Safety awareness']),
        'what_you_learn' => json_encode([
            'Wood selection and preparation',
            'Hand and power tool mastery',
            'Traditional Ugandan furniture styles',
            'Modern furniture design principles',
            'Business setup and pricing',
            'Workshop safety and maintenance'
        ]),
        'tags' => json_encode(['Carpentry', 'Furniture', 'Woodworking', 'Local Materials', 'Business']),
        'status' => 'active',
        'featured' => 'yes',
        'rating_average' => 4.7,
        'rating_count' => 892,
        'enrollment_count' => 2156,
    ],];
        'currency' => 'USD',
        'duration_hours' => 58,
        'difficulty_level' => 'Intermediate',
        'language' => 'English',
        'requirements' => json_encode(['Basic programming knowledge', 'High school mathematics', 'Computer with Python installed']),
        'what_you_learn' => json_encode([
            'Master Python for data analysis',
            'Use NumPy and Pandas effectively',
            'Create stunning data visualizations',
            'Build machine learning models',
            'Work with real-world datasets',
            'Deploy ML models to production'
        ]),
        'tags' => json_encode(['Python', 'Data Science', 'Machine Learning', 'Pandas', 'NumPy', 'TensorFlow']),
        'status' => 'active',
        'featured' => 'yes',
        'rating_average' => 4.9,
        'rating_count' => 3421,
        'enrollment_count' => 15670,
    ],
    // Mobile Development (Category 3)
    [
        'category_id' => 3,
        'title' => 'Flutter & Dart: Complete Mobile App Development',
        'slug' => 'flutter-dart-mobile-app-development',
        'description' => 'Build beautiful native mobile apps for iOS and Android using Flutter and Dart programming language.',
        'detailed_description' => 'Comprehensive Flutter course covering everything from basic widgets to advanced state management, animations, and platform integration. Build 10+ real apps throughout the course.',
        'instructor_name' => 'James Wilson',
        'instructor_bio' => 'Google Developer Expert in Flutter, mobile app consultant with 50+ published apps.',
        'instructor_avatar' => 'instructors/james-wilson.jpg',
        'cover_image' => 'courses/flutter-development.jpg',
        'preview_video' => 'previews/flutter-preview.mp4',
        'price' => 159.99,
        'currency' => 'USD',
        'duration_hours' => 48,
        'difficulty_level' => 'Intermediate',
        'language' => 'English',
        'requirements' => json_encode(['Programming experience in any language', 'Computer with Flutter SDK', 'Android Studio or VS Code']),
        'what_you_learn' => json_encode([
            'Master Dart programming language',
            'Build responsive mobile UIs',
            'Implement state management solutions',
            'Integrate with REST APIs',
            'Publish apps to app stores',
            'Handle device features and permissions'
        ]),
        'tags' => json_encode(['Flutter', 'Dart', 'Mobile Development', 'iOS', 'Android', 'Cross-platform']),
        'status' => 'active',
        'featured' => 'no',
        'rating_average' => 4.6,
        'rating_count' => 1267,
        'enrollment_count' => 6734,
    ],
    // Digital Marketing (Category 4)
    [
        'category_id' => 4,
        'title' => 'Digital Marketing Masterclass 2025',
        'slug' => 'digital-marketing-masterclass-2025',
        'description' => 'Complete digital marketing course covering SEO, social media, email marketing, PPC, and analytics.',
        'detailed_description' => 'Master all aspects of digital marketing with this comprehensive course. Learn SEO strategies, social media marketing, email campaigns, Google Ads, Facebook advertising, and how to measure ROI effectively.',
        'instructor_name' => 'Marketing Pro Academy',
        'instructor_bio' => 'Team of certified digital marketing experts with combined 50+ years of industry experience.',
        'instructor_avatar' => 'instructors/marketing-pro.jpg',
        'cover_image' => 'courses/digital-marketing.jpg',
        'preview_video' => 'previews/marketing-preview.mp4',
        'price' => 129.99,
        'currency' => 'USD',
        'duration_hours' => 35,
        'difficulty_level' => 'Beginner',
        'language' => 'English',
        'requirements' => json_encode(['Basic computer skills', 'Interest in marketing', 'Business mindset']),
        'what_you_learn' => json_encode([
            'Create effective SEO strategies',
            'Master social media marketing',
            'Build successful email campaigns',
            'Run profitable Google Ads',
            'Analyze marketing performance',
            'Develop comprehensive marketing plans'
        ]),
        'tags' => json_encode(['SEO', 'Social Media', 'Email Marketing', 'Google Ads', 'Analytics', 'Strategy']),
        'status' => 'active',
        'featured' => 'yes',
        'rating_average' => 4.5,
        'rating_count' => 2156,
        'enrollment_count' => 9823,
    ],
    // Design & UX/UI (Category 5)
    [
        'category_id' => 5,
        'title' => 'UX/UI Design Complete Course with Figma',
        'slug' => 'ux-ui-design-figma-course',
        'description' => 'Learn user experience and interface design principles while mastering Figma, the industry-standard design tool.',
        'detailed_description' => 'Comprehensive UX/UI course covering design thinking, user research, wireframing, prototyping, and visual design. Master Figma while building a complete design portfolio.',
        'instructor_name' => 'Anna Thompson',
        'instructor_bio' => 'Senior UX Designer at Airbnb, former design lead at Spotify. Design mentor and workshop facilitator.',
        'instructor_avatar' => 'instructors/anna-thompson.jpg',
        'cover_image' => 'courses/ux-ui-design.jpg',
        'preview_video' => 'previews/design-preview.mp4',
        'price' => 139.99,
        'currency' => 'USD',
        'duration_hours' => 40,
        'difficulty_level' => 'Beginner',
        'language' => 'English',
        'requirements' => json_encode(['Creative mindset', 'Computer with internet', 'Free Figma account']),
        'what_you_learn' => json_encode([
            'Master UX design principles',
            'Create user-centered designs',
            'Use Figma professionally',
            'Build interactive prototypes',
            'Conduct user research',
            'Develop a design portfolio'
        ]),
        'tags' => json_encode(['UX Design', 'UI Design', 'Figma', 'Prototyping', 'User Research', 'Portfolio']),
        'status' => 'active',
        'featured' => 'no',
        'rating_average' => 4.7,
        'rating_count' => 1543,
        'enrollment_count' => 7821,
    ],
];

// No additional courses needed - we'll generate them dynamically
$additionalCourses = [];

// Merge with main courses array  
$allCourses = array_merge($courses, $additionalCourses);
    // More Web Development
    [
        'category_id' => 1,
        'title' => 'Vue.js 3 Composition API Masterclass',
        'slug' => 'vuejs-3-composition-api-masterclass',
        'description' => 'Master Vue.js 3 with Composition API, Pinia state management, and modern development practices.',
        'instructor_name' => 'David Kim',
        'price' => 119.99,
        'duration_hours' => 32,
        'difficulty_level' => 'Intermediate',
        'rating_average' => 4.6,
        'rating_count' => 892,
        'enrollment_count' => 4567,
    ],
    [
        'category_id' => 1,
        'title' => 'Next.js Full-Stack Development',
        'slug' => 'nextjs-full-stack-development',
        'description' => 'Build production-ready applications with Next.js, including SSR, API routes, and deployment.',
        'instructor_name' => 'Lisa Park',
        'price' => 169.99,
        'duration_hours' => 45,
        'difficulty_level' => 'Advanced',
        'rating_average' => 4.8,
        'rating_count' => 1234,
        'enrollment_count' => 6789,
    ],
    // More Data Science
    [
        'category_id' => 2,
        'title' => 'Deep Learning with TensorFlow and Keras',
        'slug' => 'deep-learning-tensorflow-keras',
        'description' => 'Advanced deep learning course covering neural networks, CNNs, RNNs, and modern architectures.',
        'instructor_name' => 'Dr. Robert Singh',
        'price' => 199.99,
        'duration_hours' => 55,
        'difficulty_level' => 'Advanced',
        'rating_average' => 4.7,
        'rating_count' => 1876,
        'enrollment_count' => 8912,
    ],
    [
        'category_id' => 2,
        'title' => 'Data Visualization with D3.js and Python',
        'slug' => 'data-visualization-d3-python',
        'description' => 'Create stunning interactive data visualizations using D3.js, Python, and modern tools.',
        'instructor_name' => 'Maria Gonzalez',
        'price' => 149.99,
        'duration_hours' => 38,
        'difficulty_level' => 'Intermediate',
        'rating_average' => 4.5,
        'rating_count' => 1123,
        'enrollment_count' => 5634,
    ],
    // More Mobile Development
    [
        'category_id' => 3,
        'title' => 'React Native: Cross-Platform Mobile Development',
        'slug' => 'react-native-cross-platform',
        'description' => 'Build native mobile apps for iOS and Android using React Native and JavaScript.',
        'instructor_name' => 'Alex Turner',
        'price' => 149.99,
        'duration_hours' => 42,
        'difficulty_level' => 'Intermediate',
        'rating_average' => 4.4,
        'rating_count' => 987,
        'enrollment_count' => 5432,
    ],
    [
        'category_id' => 3,
        'title' => 'iOS App Development with Swift 5',
        'slug' => 'ios-app-development-swift-5',
        'description' => 'Learn iOS app development from scratch using Swift 5, Xcode, and iOS SDK.',
        'instructor_name' => 'Jennifer Liu',
        'price' => 159.99,
        'duration_hours' => 50,
        'difficulty_level' => 'Beginner',
        'rating_average' => 4.6,
        'rating_count' => 1345,
        'enrollment_count' => 7123,
    ],
    // Continue for other categories...
    [
        'category_id' => 4,
        'title' => 'Social Media Marketing Strategy 2025',
        'slug' => 'social-media-marketing-strategy-2025',
        'description' => 'Master Instagram, TikTok, LinkedIn, and Facebook marketing with proven strategies.',
        'instructor_name' => 'Social Media Experts',
        'price' => 99.99,
        'duration_hours' => 28,
        'difficulty_level' => 'Beginner',
        'rating_average' => 4.3,
        'rating_count' => 1567,
        'enrollment_count' => 8234,
    ],
    [
        'category_id' => 5,
        'title' => 'Adobe Creative Suite Masterclass',
        'slug' => 'adobe-creative-suite-masterclass',
        'description' => 'Master Photoshop, Illustrator, and InDesign for professional graphic design projects.',
        'instructor_name' => 'Creative Design Studio',
        'price' => 179.99,
        'duration_hours' => 60,
        'difficulty_level' => 'Intermediate',
        'rating_average' => 4.7,
        'rating_count' => 2134,
        'enrollment_count' => 9876,
    ],
    [
        'category_id' => 6,
        'title' => 'Startup Business Plan & Funding',
        'slug' => 'startup-business-plan-funding',
        'description' => 'Learn how to create a winning business plan and secure funding for your startup.',
        'instructor_name' => 'Entrepreneurship Academy',
        'price' => 129.99,
        'duration_hours' => 35,
        'difficulty_level' => 'Beginner',
        'rating_average' => 4.5,
        'rating_count' => 1789,
        'enrollment_count' => 6543,
    ],
    [
        'category_id' => 7,
        'title' => 'Professional Photography: From Beginner to Pro',
        'slug' => 'professional-photography-beginner-pro',
        'description' => 'Master camera settings, composition, lighting, and post-processing for stunning photography.',
        'instructor_name' => 'Photo Masters Academy',
        'price' => 149.99,
        'duration_hours' => 45,
        'difficulty_level' => 'Beginner',
        'rating_average' => 4.6,
        'rating_count' => 1456,
        'enrollment_count' => 7890,
    ],
];

// Merge with main courses array
$allCourses = array_merge($courses, $additionalCourses);

// Add more courses to reach 50 total (Uganda-focused)
$courseTemplates = [
    ['Agriculture & Farming', 'Modern Maize Farming Techniques in Uganda'],
    ['Agriculture & Farming', 'Coffee Growing and Processing for Export'],
    ['Agriculture & Farming', 'Poultry Farming Business Setup'],
    ['Agriculture & Farming', 'Organic Vegetable Farming'],
    ['Agriculture & Farming', 'Pig Farming and Management'],
    ['Carpentry & Woodworking', 'Furniture Making with Local Woods'],
    ['Carpentry & Woodworking', 'Building Traditional Ugandan Structures'],
    ['Carpentry & Woodworking', 'Cabinet Making for Modern Homes'],
    ['Carpentry & Woodworking', 'Wood Carving and Artistic Woodwork'],
    ['Carpentry & Woodworking', 'Tool Maintenance and Workshop Setup'],
    ['Tailoring & Fashion Design', 'Professional Tailoring for Beginners'],
    ['Tailoring & Fashion Design', 'Gomesi and Traditional Wear Design'],
    ['Tailoring & Fashion Design', 'Modern African Fashion Design'],
    ['Tailoring & Fashion Design', 'Pattern Making and Cutting'],
    ['Tailoring & Fashion Design', 'Starting a Tailoring Business'],
    ['Small Business & Entrepreneurship', 'Market Stall Business Management'],
    ['Small Business & Entrepreneurship', 'Mobile Money Agent Business'],
    ['Small Business & Entrepreneurship', 'Boda Boda Business and Fleet Management'],
    ['Small Business & Entrepreneurship', 'Beauty Salon Business Setup'],
    ['Small Business & Entrepreneurship', 'Digital Marketing for Small Businesses'],
    ['Technology & Computer Skills', 'Computer Basics for Beginners'],
    ['Technology & Computer Skills', 'Microsoft Office for Business'],
    ['Technology & Computer Skills', 'Basic Web Design and WordPress'],
    ['Technology & Computer Skills', 'Digital Literacy and Internet Safety'],
    ['Technology & Computer Skills', 'Mobile App Development Basics'],
    ['Construction & Building', 'House Construction Planning'],
    ['Construction & Building', 'Plumbing Installation and Repair'],
    ['Construction & Building', 'Electrical Wiring for Homes'],
    ['Construction & Building', 'Masonry and Concrete Work'],
    ['Construction & Building', 'Roofing Techniques and Materials'],
    ['Food Processing & Catering', 'Food Preservation and Storage'],
    ['Food Processing & Catering', 'Catering Business Management'],
    ['Food Processing & Catering', 'Local Cuisine and Restaurant Skills'],
    ['Food Processing & Catering', 'Bakery Business Setup'],
    ['Food Processing & Catering', 'Food Safety and Hygiene'],
    ['Motor Vehicle Mechanics', 'Basic Car Maintenance and Repair'],
    ['Motor Vehicle Mechanics', 'Motorcycle Repair and Maintenance'],
    ['Motor Vehicle Mechanics', 'Auto Electrical Systems'],
    ['Motor Vehicle Mechanics', 'Starting a Garage Business'],
    ['Motor Vehicle Mechanics', 'Heavy Truck and Bus Maintenance'],
    ['Financial Literacy & Savings', 'Personal Budgeting and Savings'],
    ['Financial Literacy & Savings', 'VSLA Groups Management'],
    ['Financial Literacy & Savings', 'Microfinance and Loans'],
    ['Financial Literacy & Savings', 'Investment Opportunities in Uganda'],
    ['Financial Literacy & Savings', 'Banking and Financial Services'],
    ['Languages & Communication', 'English Communication Skills'],
    ['Languages & Communication', 'Luganda Language Basics'],
    ['Languages & Communication', 'Public Speaking and Presentation'],
    ['Languages & Communication', 'Customer Service Communication'],
    ['Languages & Communication', 'Writing Skills for Business'],
];

// Generate remaining courses
$categoryMap = [
    'Agriculture & Farming' => 1,
    'Carpentry & Woodworking' => 2,
    'Tailoring & Fashion Design' => 3,
    'Small Business & Entrepreneurship' => 4,
    'Technology & Computer Skills' => 5,
    'Construction & Building' => 6,
    'Food Processing & Catering' => 7,
    'Motor Vehicle Mechanics' => 8,
    'Financial Literacy & Savings' => 9,
    'Languages & Communication' => 10,
];

$ugandanInstructors = [
    'Robert Mukasa', 'Sarah Namubiru', 'John Okello', 'Grace Nakato', 'David Ssemakula',
    'Mary Namuli', 'Patrick Wamala', 'Alice Namatovu', 'Joseph Kiwanuka', 'Betty Nalwanga',
    'Samuel Bbosa', 'Jane Kisakye', 'Moses Lubega', 'Florence Namusoke', 'Emmanuel Kaggwa'
];

while (count($allCourses) < 50) {
    $template = $courseTemplates[array_rand($courseTemplates)];
    $categoryName = $template[0];
    $courseTitle = $template[1];
    
    // Add randomization to avoid duplicate slugs
    $uniqueTitle = $courseTitle . " " . rand(2025, 2026);
    
    $allCourses[] = [
        'category_id' => $categoryMap[$categoryName],
        'title' => $uniqueTitle,
        'slug' => Str::slug($uniqueTitle),
        'description' => "Master {$courseTitle} with practical hands-on training and expert guidance from experienced Ugandan instructors.",
        'instructor_name' => $ugandanInstructors[array_rand($ugandanInstructors)],
        'price' => rand(200000, 1000000), // UGX 200,000 - 1,000,000
        'currency' => 'UGX',
        'duration_hours' => rand(20, 60),
        'difficulty_level' => ['Beginner', 'Intermediate', 'Advanced'][rand(0, 2)],
        'rating_average' => rand(40, 50) / 10,
        'rating_count' => rand(100, 3000),
        'enrollment_count' => rand(1000, 15000),
    ];
}

// Insert courses
echo "🎓 Inserting courses...\n";
DB::table('courses')->truncate();

$insertedSlugs = [];
foreach (array_slice($allCourses, 0, 50) as $index => $course) {
    $defaults = [
        'detailed_description' => $course['description'] . ' This comprehensive course includes hands-on projects, real-world examples, and expert instruction to help you master the subject.',
        'instructor_bio' => 'Experienced professional with years of industry expertise.',
        'instructor_avatar' => 'instructors/default.jpg',
        'cover_image' => 'courses/default.jpg',
        'preview_video' => 'previews/default.mp4',
        'currency' => 'USD',
        'language' => 'English',
        'requirements' => json_encode(['Basic computer skills', 'Internet connection']),
        'what_you_learn' => json_encode(['Master the fundamentals', 'Apply knowledge practically', 'Build real projects']),
        'tags' => json_encode(['Skills', 'Learning', 'Practice']),
        'status' => 'active',
        'featured' => rand(0, 1) ? 'yes' : 'no',
        'created_at' => now(),
        'updated_at' => now(),
    ];
    
    $course = array_merge($defaults, $course);
    
    // Ensure unique slug
    $baseSlug = $course['slug'];
    $counter = 1;
    while (in_array($course['slug'], $insertedSlugs)) {
        $course['slug'] = $baseSlug . '-' . $counter;
        $counter++;
    }
    $insertedSlugs[] = $course['slug'];
    
    DB::table('courses')->insert($course);
}

echo "✅ Inserted 50 courses\n";

// Generate Course Units (realistic structure)
echo "📖 Generating course units...\n";
DB::table('course_units')->truncate();

$unitTemplates = [
    'Introduction and Getting Started',
    'Setting Up the Development Environment',
    'Understanding the Fundamentals',
    'Core Concepts and Principles',
    'Practical Implementation',
    'Advanced Techniques',
    'Best Practices and Patterns',
    'Real-World Project Building',
    'Testing and Debugging',
    'Deployment and Production',
    'Performance Optimization',
    'Final Project and Portfolio'
];

$unitId = 1;
for ($courseId = 1; $courseId <= 50; $courseId++) {
    $unitsCount = rand(8, 15); // Realistic number of units per course
    
    for ($i = 1; $i <= $unitsCount; $i++) {
        $unitTitle = $unitTemplates[($i - 1) % count($unitTemplates)];
        if ($i > count($unitTemplates)) {
            $unitTitle = "Advanced Topic " . ($i - count($unitTemplates));
        }
        
        DB::table('course_units')->insert([
            'id' => $unitId++,
            'course_id' => $courseId,
            'title' => $unitTitle,
            'description' => "Comprehensive coverage of {$unitTitle} with practical examples and exercises.",
            'sort_order' => $i,
            'duration_minutes' => rand(30, 90),
            'is_preview' => $i <= 2 ? 'yes' : 'no', // First 2 units are preview
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

echo "✅ Generated course units\n";

// Generate Course Materials (10+ per course)
echo "📹 Generating course materials...\n";
DB::table('course_materials')->truncate();

$materialTypes = ['video', 'text', 'pdf', 'quiz', 'exercise', 'download'];
$materialTitles = [
    'Introduction Video',
    'Concept Overview',
    'Hands-on Exercise',
    'Code Examples',
    'Reference Guide',
    'Practice Quiz',
    'Additional Resources',
    'Case Study',
    'Implementation Tutorial',
    'Summary and Review'
];

$materialId = 1;
$units = DB::table('course_units')->get();

foreach ($units as $unit) {
    $materialsCount = rand(3, 8); // 3-8 materials per unit
    
    for ($i = 1; $i <= $materialsCount; $i++) {
        $type = $materialTypes[array_rand($materialTypes)];
        $title = $materialTitles[array_rand($materialTitles)];
        
        DB::table('course_materials')->insert([
            'id' => $materialId++,
            'unit_id' => $unit->id,
            'title' => $title,
            'type' => $type,
            'content_url' => $type === 'video' ? "videos/material_{$materialId}.mp4" : 
                           ($type === 'pdf' ? "pdfs/material_{$materialId}.pdf" : null),
            'content_text' => $type === 'text' ? "Detailed content for {$title}. This material covers important concepts with practical examples." : null,
            'duration_seconds' => $type === 'video' ? rand(300, 1800) : 0, // 5-30 minutes for videos
            'file_size' => $type === 'video' ? rand(50, 500) * 1024 * 1024 : rand(1, 10) * 1024 * 1024, // MB in bytes
            'sort_order' => $i,
            'is_downloadable' => in_array($type, ['pdf', 'download']) ? 'yes' : 'no',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

echo "✅ Generated course materials\n";

// Generate Course Notifications (50 entries)
echo "🔔 Generating course notifications...\n";
DB::table('course_notifications')->truncate();

$notificationTypes = [
    'course_enrollment',
    'new_material',
    'assignment_due',
    'course_update',
    'completion_reminder',
    'achievement_earned',
    'instructor_announcement',
    'quiz_available',
    'deadline_approaching',
    'certificate_ready'
];

$notificationTitles = [
    'Welcome to the Course!',
    'New Material Available',
    'Assignment Due Soon',
    'Course Content Updated',
    'Complete Your Progress',
    'Achievement Unlocked!',
    'Important Announcement',
    'New Quiz Available',
    'Deadline Reminder',
    'Certificate Ready for Download'
];

for ($i = 1; $i <= 50; $i++) {
    $type = $notificationTypes[array_rand($notificationTypes)];
    $title = $notificationTitles[array_rand($notificationTitles)];
    $courseId = rand(1, 50);
    
    DB::table('course_notifications')->insert([
        'user_id' => 1, // Using user_id = 1 as requested
        'course_id' => $courseId,
        'type' => $type,
        'title' => $title,
        'message' => "This is a notification about {$title}. Please check your course progress and stay up to date with the latest updates.",
        'read_status' => rand(0, 1) ? 'read' : 'unread',
        'action_url' => "/courses/{$courseId}",
        'created_at' => now()->subDays(rand(0, 30)),
        'updated_at' => now(),
    ]);
}

echo "✅ Generated 50 course notifications\n";

// Generate Course Quizzes
echo "❓ Generating course quizzes...\n";
DB::table('course_quizzes')->truncate();

$quizQuestions = [
    [
        'question' => 'What is the primary purpose of this concept?',
        'type' => 'multiple_choice',
        'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
        'correct_answer' => 'Option B'
    ],
    [
        'question' => 'Which of the following best describes the implementation?',
        'type' => 'multiple_choice', 
        'options' => ['Implementation A', 'Implementation B', 'Implementation C', 'Implementation D'],
        'correct_answer' => 'Implementation C'
    ],
    [
        'question' => 'True or False: This statement is correct.',
        'type' => 'true_false',
        'options' => ['True', 'False'],
        'correct_answer' => 'True'
    ]
];

$quizId = 1;
foreach ($units as $unit) {
    if (rand(0, 2) === 0) { // ~33% of units have quizzes
        DB::table('course_quizzes')->insert([
            'id' => $quizId++,
            'unit_id' => $unit->id,
            'title' => "Quiz: {$unit->title}",
            'description' => "Test your knowledge of {$unit->title} concepts and principles.",
            'questions' => json_encode($quizQuestions),
            'passing_score' => rand(60, 80),
            'time_limit_minutes' => rand(10, 30),
            'max_attempts' => rand(2, 5),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

echo "✅ Generated course quizzes\n";

// Generate Course Subscriptions
echo "💳 Generating course subscriptions...\n";
DB::table('course_subscriptions')->truncate();

$subscriptionTypes = ['free', 'paid', 'premium'];
$paymentStatuses = ['completed', 'pending', 'failed'];

for ($i = 1; $i <= 100; $i++) { // More subscriptions for realism
    $courseId = rand(1, 50);
    $course = DB::table('courses')->where('id', $courseId)->first();
    $subscriptionType = $subscriptionTypes[array_rand($subscriptionTypes)];
    
    DB::table('course_subscriptions')->insert([
        'user_id' => 1,
        'course_id' => $courseId,
        'subscription_type' => $subscriptionType,
        'status' => 'active',
        'subscribed_at' => now()->subDays(rand(0, 90)),
        'expires_at' => $subscriptionType === 'free' ? null : now()->addDays(rand(30, 365)),
        'payment_status' => $subscriptionType === 'free' ? 'completed' : $paymentStatuses[array_rand($paymentStatuses)],
        'payment_amount' => $subscriptionType === 'free' ? 0.00 : $course->price,
        'payment_currency' => 'USD',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "✅ Generated course subscriptions\n";

// Generate Course Progress
echo "📈 Generating course progress...\n";
DB::table('course_progress')->truncate();

$materials = DB::table('course_materials')->get();
$progressId = 1;

foreach ($materials->take(200) as $material) { // Sample progress for realism
    $unit = DB::table('course_units')->where('id', $material->unit_id)->first();
    $progress = rand(0, 100);
    
    DB::table('course_progress')->insert([
        'id' => $progressId++,
        'user_id' => 1,
        'course_id' => $unit->course_id,
        'unit_id' => $unit->id,
        'material_id' => $material->id,
        'progress_percentage' => $progress,
        'time_spent_seconds' => rand(300, 3600), // 5 minutes to 1 hour
        'completed' => $progress >= 100 ? 'yes' : 'no',
        'completed_at' => $progress >= 100 ? now()->subDays(rand(0, 30)) : null,
        'last_accessed_at' => now()->subDays(rand(0, 7)),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "✅ Generated course progress\n";

// Generate Course Quiz Answers
echo "✍️ Generating quiz answers...\n";
DB::table('course_quiz_answers')->truncate();

$quizzes = DB::table('course_quizzes')->get();
$answerId = 1;

foreach ($quizzes as $quiz) {
    $attempts = rand(1, 3); // 1-3 attempts per quiz
    
    for ($attempt = 1; $attempt <= $attempts; $attempt++) {
        $score = rand(40, 100);
        $passed = $score >= $quiz->passing_score;
        
        DB::table('course_quiz_answers')->insert([
            'id' => $answerId++,
            'user_id' => 1,
            'quiz_id' => $quiz->id,
            'answers' => json_encode([
                'question_1' => 'Option B',
                'question_2' => 'Implementation C',
                'question_3' => 'True'
            ]),
            'score' => $score,
            'passed' => $passed ? 'yes' : 'no',
            'attempt_number' => $attempt,
            'time_taken_seconds' => rand(300, 1800), // 5-30 minutes
            'completed_at' => now()->subDays(rand(0, 20)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

echo "✅ Generated quiz answers\n";

// Generate Course Certificates (20 entries)
echo "🏆 Generating course certificates...\n";
DB::table('course_certificates')->truncate();

$certificatesData = [];
$usedUserCourseCombinations = [];

for ($i = 1; $i <= 20; $i++) {
    $userId = rand(1, 10);
    $courseId = rand(1, 50);
    $userCourseKey = $userId . '-' . $courseId;
    
    // Skip if this combination already exists
    if (in_array($userCourseKey, $usedUserCourseCombinations)) {
        $i--; // Decrement to retry
        continue;
    }
    $usedUserCourseCombinations[] = $userCourseKey;
    
    $completionDate = now()->subDays(rand(0, 60));
    
    DB::table('course_certificates')->insert([
        'user_id' => $userId,
        'course_id' => $courseId,
        'certificate_number' => 'CERT-' . strtoupper(Str::random(8)),
        'issued_date' => $completionDate->addDays(1),
        'completion_date' => $completionDate,
        'grade' => rand(70, 100),
        'pdf_url' => "certificates/cert_{$i}.pdf",
        'verification_code' => strtoupper(Str::random(12)),
        'status' => 'issued',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "✅ Generated 20 course certificates\n";

// Generate Course Reviews
echo "⭐ Generating course reviews...\n";
DB::table('course_reviews')->truncate();

$reviewTexts = [
    "Excellent course! The instructor explains concepts clearly and the projects are very practical.",
    "Great content and well-structured. I learned a lot and can immediately apply what I've learned.",
    "Very comprehensive course. The pace is perfect and the examples are relevant.",
    "Outstanding quality! This course exceeded my expectations and provided real value.",
    "Solid course with good practical examples. Would recommend to others.",
    "Well organized and easy to follow. The instructor is knowledgeable and engaging.",
    "Good course overall. Some sections could be more detailed but generally satisfied.",
    "Amazing course! The best investment I've made in my learning journey.",
    "Practical and to the point. No fluff, just valuable content that works.",
    "Highly recommended! This course gave me the skills I needed for my career."
];

for ($i = 1; $i <= 150; $i++) { // More reviews for realism
    $userId = rand(1, 10);
    $courseId = rand(1, 50);
    $userCourseKey = $userId . '-' . $courseId;
    
    // Skip if this combination already exists (one review per user per course)
    if (DB::table('course_reviews')->where('user_id', $userId)->where('course_id', $courseId)->exists()) {
        $i--; // Decrement to retry
        continue;
    }
    
    $rating = rand(3, 5); // Mostly positive reviews
    
    DB::table('course_reviews')->insert([
        'user_id' => $userId,
        'course_id' => $courseId,
        'rating' => $rating,
        'review_text' => $reviewTexts[array_rand($reviewTexts)],
        'helpful_count' => rand(0, 25),
        'status' => 'approved',
        'created_at' => now()->subDays(rand(0, 90)),
        'updated_at' => now(),
    ]);
}

echo "✅ Generated course reviews\n";

echo "\n🎉 Course Module Dummy Data Generation Complete!\n";
echo "📊 Summary:\n";
echo "   - 10 Course Categories\n";
echo "   - 50 Courses\n";
echo "   - " . DB::table('course_units')->count() . " Course Units\n";
echo "   - " . DB::table('course_materials')->count() . " Course Materials\n";
echo "   - 50 Course Notifications\n";
echo "   - " . DB::table('course_quizzes')->count() . " Course Quizzes\n";
echo "   - " . DB::table('course_subscriptions')->count() . " Course Subscriptions\n";
echo "   - " . DB::table('course_progress')->count() . " Course Progress Entries\n";
echo "   - " . DB::table('course_quiz_answers')->count() . " Quiz Answers\n";
echo "   - 20 Course Certificates\n";
echo "   - 150 Course Reviews\n\n";

echo "✅ All data is interconnected and realistic, based on leading online course platforms!\n";
echo "🖼️ Note: Course cover images should be manually added to storage/app/public/images/courses/\n";
