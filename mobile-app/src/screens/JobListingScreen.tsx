import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Alert,
  ScrollView,
} from 'react-native';
import { SearchBar } from 'react-native-elements';
import { useNavigation } from '@react-navigation/native';
import { JobCard } from '../components/JobCard';
import { FilterBottomSheet } from '../components/FilterBottomSheet';
import { ApiService } from '../services/ApiService';
import { Job, JobFilters } from '../types/Job';
import { colors, spacing, typography } from '../theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface JobListingScreenProps {
  route?: any;
}

export const JobListingScreen: React.FC<JobListingScreenProps> = ({ route }) => {
  const navigation = useNavigation();
  const [jobs, setJobs] = useState<Job[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [showFilters, setShowFilters] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [hasMoreData, setHasMoreData] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  
  const [activeFilters, setActiveFilters] = useState<JobFilters>({
    category: null,
    district: null,
    employment_status: null,
    workplace: null,
    salary_min: null,
    salary_max: null,
    deadline_from: null,
    deadline_to: null,
    company_id: null,
    experience_level: null,
    job_level: null,
    required_video_cv: null,
    min_age: null,
    max_age: null,
    required_skills: [],
    education_level: null,
    sort_by: 'created_at',
    sort_order: 'desc',
    industry: null,
    gender: null,
    experience_field: null,
  });

  const [filterCounts, setFilterCounts] = useState({
    total: 0,
    applied: 0,
  });

  const [defaultFilters, setDefaultFilters] = useState<JobFilters | null>(null);

  // Initialize default filters from route parameters
  useEffect(() => {
    if (route?.params) {
      const routeFilters: Partial<JobFilters> = {};
      
      // Extract filters from route parameters
      if (route.params.category) {
        routeFilters.category = route.params.category;
      }
      if (route.params.district) {
        routeFilters.district = route.params.district;
      }
      if (route.params.employment_status) {
        routeFilters.employment_status = route.params.employment_status;
      }
      if (route.params.workplace) {
        routeFilters.workplace = route.params.workplace;
      }
      if (route.params.company_id) {
        routeFilters.company_id = route.params.company_id;
      }
      if (route.params.salary_min) {
        routeFilters.salary_min = route.params.salary_min;
      }
      if (route.params.salary_max) {
        routeFilters.salary_max = route.params.salary_max;
      }
      if (route.params.experience_level) {
        routeFilters.experience_level = route.params.experience_level;
      }
      if (route.params.education_level) {
        routeFilters.education_level = route.params.education_level;
      }
      if (route.params.industry) {
        routeFilters.industry = route.params.industry;
      }
      if (route.params.gender) {
        routeFilters.gender = route.params.gender;
      }
      if (route.params.search) {
        setSearchQuery(route.params.search);
      }
      if (route.params.sort_by) {
        routeFilters.sort_by = route.params.sort_by;
        routeFilters.sort_order = route.params.sort_order || 'desc';
      }

      if (Object.keys(routeFilters).length > 0) {
        const newFilters = { ...activeFilters, ...routeFilters };
        setActiveFilters(newFilters);
        setDefaultFilters(newFilters);
      }
    }
  }, [route?.params]);

  // Load jobs from API
  const loadJobs = useCallback(async (
    page: number = 1,
    filters: JobFilters = activeFilters,
    search: string = searchQuery,
    append: boolean = false
  ) => {
    try {
      if (page === 1) {
        setLoading(true);
      } else {
        setLoadingMore(true);
      }

      const params = {
        page,
        per_page: 20,
        search,
        status: 'Active',
        // Core filters
        category: filters.category,
        district: filters.district,
        employment_status: filters.employment_status,
        workplace: filters.workplace,
        salary: filters.salary_min,
        deadline: filters.deadline_from,
        company: filters.company_id,
        experience_field: filters.experience_field,
        industry: filters.industry,
        gender: filters.gender,
        // Sorting
        sort: filters.sort_by === 'created_at' && filters.sort_order === 'desc' ? 'Newest' :
              filters.sort_by === 'created_at' && filters.sort_order === 'asc' ? 'Oldest' :
              filters.sort_by === 'maximum_salary' && filters.sort_order === 'desc' ? 'High Salary' :
              filters.sort_by === 'minimum_salary' && filters.sort_order === 'asc' ? 'Low Salary' :
              'Newest',
        // Remove null values
        ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== null && v !== '' && v !== undefined)),
      };

      const response = await ApiService.getJobs(params);
      const newJobs = response.data.data;
      
      if (append) {
        setJobs(prev => [...prev, ...newJobs]);
      } else {
        setJobs(newJobs);
      }

      setHasMoreData(response.data.current_page < response.data.last_page);
      setCurrentPage(response.data.current_page);
      
      // Update filter counts
      setFilterCounts({
        total: response.data.total,
        applied: Object.values(filters).filter(v => v !== null && v !== '').length,
      });

    } catch (error) {
      console.error('Error loading jobs:', error);
      Alert.alert('Error', 'Failed to load jobs. Please try again.');
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  }, [activeFilters, searchQuery]);

  // Initial load
  useEffect(() => {
    loadJobs(1);
  }, []);

  // Handle search
  const handleSearch = useCallback((text: string) => {
    setSearchQuery(text);
    setCurrentPage(1);
    loadJobs(1, activeFilters, text, false);
  }, [activeFilters]);

  // Handle filter changes
  const handleFilterChange = useCallback((newFilters: JobFilters) => {
    setActiveFilters(newFilters);
    setCurrentPage(1);
    loadJobs(1, newFilters, searchQuery, false);
    setShowFilters(false);
  }, [searchQuery]);

  // Handle refresh
  const handleRefresh = useCallback(() => {
    setRefreshing(true);
    setCurrentPage(1);
    loadJobs(1, activeFilters, searchQuery, false);
  }, [activeFilters, searchQuery]);

  // Handle load more
  const handleLoadMore = useCallback(() => {
    if (!loadingMore && hasMoreData) {
      loadJobs(currentPage + 1, activeFilters, searchQuery, true);
    }
  }, [currentPage, activeFilters, searchQuery, loadingMore, hasMoreData]);

  // Clear all filters
  const clearFilters = useCallback(() => {
    const baseFilters: JobFilters = {
      category: null,
      district: null,
      employment_status: null,
      workplace: null,
      salary_min: null,
      salary_max: null,
      deadline_from: null,
      deadline_to: null,
      company_id: null,
      experience_level: null,
      job_level: null,
      required_video_cv: null,
      min_age: null,
      max_age: null,
      required_skills: [],
      education_level: null,
      sort_by: 'created_at',
      sort_order: 'desc',
      industry: null,
      gender: null,
      experience_field: null,
    };

    // If we have default filters from route, merge them with base filters
    const filtersToUse = defaultFilters ? { ...baseFilters, ...defaultFilters } : baseFilters;
    
    setActiveFilters(filtersToUse);
    setSearchQuery('');
    setCurrentPage(1);
    loadJobs(1, filtersToUse, '', false);
  }, [defaultFilters]);

  // Handle quick sort
  const handleQuickSort = useCallback((sortBy: string, sortOrder: string) => {
    const newFilters = { ...activeFilters, sort_by: sortBy, sort_order: sortOrder };
    setActiveFilters(newFilters);
    setCurrentPage(1);
    loadJobs(1, newFilters, searchQuery, false);
  }, [activeFilters, searchQuery]);

  // Show sort options (placeholder - could be a modal or action sheet)
  const showSortOptions = useCallback(() => {
    Alert.alert(
      'Sort Options',
      'Choose how to sort the job listings',
      [
        { text: 'Newest First', onPress: () => handleQuickSort('created_at', 'desc') },
        { text: 'Oldest First', onPress: () => handleQuickSort('created_at', 'asc') },
        { text: 'High Salary', onPress: () => handleQuickSort('maximum_salary', 'desc') },
        { text: 'Low Salary', onPress: () => handleQuickSort('minimum_salary', 'asc') },
        { text: 'Deadline (Earliest)', onPress: () => handleQuickSort('deadline', 'asc') },
        { text: 'Cancel', style: 'cancel' },
      ]
    );
  }, [handleQuickSort]);

  // Navigate to job details
  const navigateToJobDetails = useCallback((job: Job) => {
    navigation.navigate('JobDetails', { job });
  }, [navigation]);

  // Render job item
  const renderJobItem = ({ item }: { item: Job }) => (
    <JobCard
      job={item}
      onPress={() => navigateToJobDetails(item)}
      onBookmark={() => {
        // Handle bookmark functionality
      }}
    />
  );

  // Render empty state
  const renderEmptyState = () => (
    <View style={styles.emptyState}>
      <Icon name="work-off" size={64} color={colors.gray} />
      <Text style={styles.emptyTitle}>No Jobs Found</Text>
      <Text style={styles.emptyDescription}>
        Try adjusting your search criteria or filters
      </Text>
      {filterCounts.applied > 0 && (
        <TouchableOpacity style={styles.clearButton} onPress={clearFilters}>
          <Text style={styles.clearButtonText}>Clear Filters</Text>
        </TouchableOpacity>
      )}
    </View>
  );

  // Render footer
  const renderFooter = () => {
    if (!loadingMore) return null;
    return (
      <View style={styles.loadingFooter}>
        <ActivityIndicator size="small" color={colors.primary} />
        <Text style={styles.loadingText}>Loading more jobs...</Text>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.title}>Job Listings</Text>
        <Text style={styles.subtitle}>
          {filterCounts.total} jobs available
        </Text>
      </View>

      {/* Search Bar */}
      <SearchBar
        placeholder="Search jobs..."
        onChangeText={handleSearch}
        value={searchQuery}
        containerStyle={styles.searchContainer}
        inputContainerStyle={styles.searchInput}
        inputStyle={styles.searchText}
        searchIcon={{ size: 20, color: colors.gray }}
        clearIcon={{ size: 20, color: colors.gray }}
        lightTheme
      />

      {/* Filter Bar */}
      <View style={styles.filterBar}>
        <TouchableOpacity
          style={[styles.filterButton, filterCounts.applied > 0 && styles.filterButtonActive]}
          onPress={() => setShowFilters(true)}
        >
          <Icon name="filter-list" size={20} color={filterCounts.applied > 0 ? colors.white : colors.primary} />
          <Text style={[styles.filterButtonText, filterCounts.applied > 0 && styles.filterButtonTextActive]}>
            Filters
          </Text>
          {filterCounts.applied > 0 && (
            <View style={styles.filterBadge}>
              <Text style={styles.filterBadgeText}>{filterCounts.applied}</Text>
            </View>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.sortButton}
          onPress={() => {
            // Toggle sort options
            showSortOptions();
          }}
        >
          <Icon name="sort" size={20} color={colors.primary} />
          <Text style={styles.sortButtonText}>Sort</Text>
        </TouchableOpacity>
      </View>

      {/* Quick Sort Options */}
      <View style={styles.quickSortContainer}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
          <TouchableOpacity
            style={[styles.quickSortItem, activeFilters.sort_by === 'created_at' && activeFilters.sort_order === 'desc' && styles.quickSortItemActive]}
            onPress={() => handleQuickSort('created_at', 'desc')}
          >
            <Text style={[styles.quickSortText, activeFilters.sort_by === 'created_at' && activeFilters.sort_order === 'desc' && styles.quickSortTextActive]}>
              Newest
            </Text>
          </TouchableOpacity>
          
          <TouchableOpacity
            style={[styles.quickSortItem, activeFilters.sort_by === 'maximum_salary' && activeFilters.sort_order === 'desc' && styles.quickSortItemActive]}
            onPress={() => handleQuickSort('maximum_salary', 'desc')}
          >
            <Text style={[styles.quickSortText, activeFilters.sort_by === 'maximum_salary' && activeFilters.sort_order === 'desc' && styles.quickSortTextActive]}>
              High Salary
            </Text>
          </TouchableOpacity>
          
          <TouchableOpacity
            style={[styles.quickSortItem, activeFilters.sort_by === 'deadline' && activeFilters.sort_order === 'asc' && styles.quickSortItemActive]}
            onPress={() => handleQuickSort('deadline', 'asc')}
          >
            <Text style={[styles.quickSortText, activeFilters.sort_by === 'deadline' && activeFilters.sort_order === 'asc' && styles.quickSortTextActive]}>
              Deadline
            </Text>
          </TouchableOpacity>
          
          <TouchableOpacity
            style={[styles.quickSortItem, activeFilters.sort_by === 'created_at' && activeFilters.sort_order === 'asc' && styles.quickSortItemActive]}
            onPress={() => handleQuickSort('created_at', 'asc')}
          >
            <Text style={[styles.quickSortText, activeFilters.sort_by === 'created_at' && activeFilters.sort_order === 'asc' && styles.quickSortTextActive]}>
              Oldest
            </Text>
          </TouchableOpacity>
        </ScrollView>
      </View>

      {/* Active Filters Display */}
      {filterCounts.applied > 0 && (
        <View style={styles.activeFiltersContainer}>
          <Text style={styles.activeFiltersTitle}>Active Filters:</Text>
          <View style={styles.activeFiltersRow}>
            {Object.entries(activeFilters).map(([key, value]) => {
              if (value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
                return null;
              }
              return (
                <View key={key} style={styles.activeFilterChip}>
                  <Text style={styles.activeFilterText}>
                    {key.replace('_', ' ')}: {Array.isArray(value) ? value.join(', ') : String(value)}
                  </Text>
                </View>
              );
            })}
          </View>
        </View>
      )}

      {/* Job List */}
      {loading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.primary} />
          <Text style={styles.loadingText}>Loading jobs...</Text>
        </View>
      ) : (
        <FlatList
          data={jobs}
          renderItem={renderJobItem}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContainer}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              colors={[colors.primary]}
            />
          }
          onEndReached={handleLoadMore}
          onEndReachedThreshold={0.1}
          ListFooterComponent={renderFooter}
          ListEmptyComponent={renderEmptyState}
          showsVerticalScrollIndicator={false}
        />
      )}

      {/* Filter Bottom Sheet */}
      <FilterBottomSheet
        visible={showFilters}
        onClose={() => setShowFilters(false)}
        currentFilters={activeFilters}
        onApplyFilters={handleFilterChange}
        onClearFilters={clearFilters}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    padding: spacing.md,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  subtitle: {
    fontSize: typography.fontSize.sm,
    color: colors.gray,
  },
  searchContainer: {
    backgroundColor: colors.white,
    borderBottomColor: colors.border,
    borderTopColor: colors.border,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  searchInput: {
    backgroundColor: colors.lightGray,
    borderRadius: 8,
  },
  searchText: {
    fontSize: typography.fontSize.md,
    color: colors.text,
  },
  filterBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  filterButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: colors.primary,
  },
  filterButtonActive: {
    backgroundColor: colors.primary,
  },
  filterButtonText: {
    marginLeft: spacing.xs,
    fontSize: typography.fontSize.sm,
    color: colors.primary,
    fontWeight: typography.fontWeight.medium,
  },
  filterButtonTextActive: {
    color: colors.white,
  },
  filterBadge: {
    backgroundColor: colors.white,
    borderRadius: 10,
    paddingHorizontal: spacing.xs,
    marginLeft: spacing.xs,
  },
  filterBadgeText: {
    fontSize: typography.fontSize.xs,
    color: colors.primary,
    fontWeight: typography.fontWeight.bold,
  },
  sortButton: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  sortButtonText: {
    marginLeft: spacing.xs,
    fontSize: typography.fontSize.sm,
    color: colors.primary,
    fontWeight: typography.fontWeight.medium,
  },
  quickSortContainer: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.white,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  quickSortItem: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    marginRight: spacing.sm,
    borderRadius: 20,
    backgroundColor: colors.lightGray,
  },
  quickSortItemActive: {
    backgroundColor: colors.primary,
  },
  quickSortText: {
    fontSize: typography.fontSize.sm,
    color: colors.text,
    fontWeight: typography.fontWeight.medium,
  },
  quickSortTextActive: {
    color: colors.white,
  },
  activeFiltersContainer: {
    backgroundColor: colors.lightGray,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
  },
  activeFiltersTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  activeFiltersRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  activeFilterChip: {
    backgroundColor: colors.primary,
    borderRadius: 16,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    marginRight: spacing.xs,
    marginBottom: spacing.xs,
  },
  activeFilterText: {
    fontSize: typography.fontSize.xs,
    color: colors.white,
    textTransform: 'capitalize',
  },
  listContainer: {
    paddingHorizontal: spacing.md,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: spacing.md,
    fontSize: typography.fontSize.md,
    color: colors.gray,
  },
  loadingFooter: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    paddingVertical: spacing.md,
  },
  emptyState: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: spacing.lg,
  },
  emptyTitle: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.text,
    marginTop: spacing.md,
    marginBottom: spacing.sm,
  },
  emptyDescription: {
    fontSize: typography.fontSize.md,
    color: colors.gray,
    textAlign: 'center',
    marginBottom: spacing.md,
  },
  clearButton: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderRadius: 8,
  },
  clearButtonText: {
    color: colors.white,
    fontSize: typography.fontSize.md,
    fontWeight: typography.fontWeight.medium,
  },
});
