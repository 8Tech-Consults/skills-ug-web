# Skills UG Mobile App - Advanced Job Filters

This React Native mobile application provides advanced job search functionality with comprehensive filtering options.

## Features

### Advanced Job Filtering
- **Basic Filters**: Search by keyword, category, district, deadline
- **Advanced Filters**: Industry, gender, experience field, company, salary range
- **Sort Options**: Quick sort bar with options like newest, oldest, highest salary
- **Employment Status**: Full-time, part-time, contract, internship
- **Workplace Type**: On-site, remote, hybrid

### Filter Implementation
- **Bottom Sheet UI**: Modern sliding bottom sheet for advanced filters
- **Default Filter Support**: Screen accepts default filters via route params
- **Filter Persistence**: Filters are maintained across navigation
- **Reset Functionality**: Clear all filters or reset to defaults

## File Structure

```
src/
├── screens/
│   └── JobListingScreen.tsx      # Main job listing screen with filters
├── components/
│   ├── JobCard.tsx               # Job item display component
│   └── FilterBottomSheet.tsx     # Advanced filter bottom sheet
├── types/
│   └── Job.ts                    # TypeScript definitions
├── services/
│   └── ApiService.ts             # API service layer
└── theme/
    └── index.ts                  # Theme configuration
```

## API Integration

The app integrates with the backend API endpoint `/api/jobs` with the following filter parameters:

- `search`: Text search in job titles and descriptions
- `category`: Job category ID
- `industry`: Industry type
- `district`: Location district
- `deadline`: Application deadline
- `company`: Company name
- `salary_min` / `salary_max`: Salary range
- `employment_status`: Employment type
- `workplace`: Workplace type
- `gender`: Gender preference
- `experience_field`: Required experience field
- `sort`: Sort criteria

## Usage

### JobListingScreen Props

```typescript
interface JobListingScreenProps {
  route: {
    params?: {
      defaultFilters?: Partial<JobFilters>;
      title?: string;
    };
  };
}
```

### Example Navigation

```typescript
// Navigate with default filters
navigation.navigate('JobListing', {
  defaultFilters: {
    category: '1',
    industry: 'technology',
    district: 'kampala'
  },
  title: 'Tech Jobs in Kampala'
});
```

### Filter Bottom Sheet

The `FilterBottomSheet` component provides:

- **Input Fields**: Text inputs for search, company, experience field
- **Pickers**: Dropdowns for category, industry, district, gender
- **Date Picker**: Deadline selection
- **Slider**: Salary range selection
- **Buttons**: Apply and reset functionality

## Key Components

### JobListingScreen
- Manages job data fetching and filtering
- Handles search input and quick sort options
- Integrates with FilterBottomSheet for advanced filtering
- Supports default filters from navigation params

### FilterBottomSheet
- Provides comprehensive filter UI
- Handles filter state management
- Calls API for dynamic filter options (categories, districts)
- Applies filters and notifies parent component

### JobCard
- Displays individual job information
- Shows key details like title, company, location, salary
- Provides action buttons for job interactions

## Dependencies

Key React Native packages used:

- `@react-navigation/native`: Navigation
- `@react-native-picker/picker`: Dropdown selectors
- `@react-native-community/datetimepicker`: Date selection
- `@react-native-community/slider`: Salary range slider
- `react-native-elements`: UI components
- `react-native-vector-icons`: Icons

## Installation

```bash
# Install dependencies
npm install

# iOS setup
cd ios && pod install

# Run on iOS
npx react-native run-ios

# Run on Android
npx react-native run-android
```

## Development Notes

### Filter Flow
1. User opens JobListingScreen
2. Default filters applied if provided via route params
3. User can use search bar or quick sort options
4. Advanced filters accessed via bottom sheet
5. Filters applied to API request
6. Results updated in real-time

### State Management
- Local state for job data and filters
- API service abstraction for backend calls
- Filter state passed between components

### Error Handling
- API error handling in service layer
- Loading states for async operations
- Fallback UI for empty results

### Performance
- Debounced search input
- Efficient list rendering with FlatList
- Memoized filter components

## Future Enhancements

- Filter history and saved searches
- Location-based filtering with maps
- Push notifications for job alerts
- Offline capability with local storage
- Advanced job matching algorithms

## Backend Integration

Ensure the backend API supports all filter parameters mentioned in the types. The current implementation matches the Laravel backend structure found in the main project.

## Testing

Run tests with:
```bash
npm test
```

## Contributing

1. Follow existing code patterns and TypeScript definitions
2. Test filter functionality thoroughly
3. Ensure UI responsiveness across devices
4. Update documentation for new features
