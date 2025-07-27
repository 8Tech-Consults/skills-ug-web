import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  TouchableOpacity,
  ScrollView,
  TextInput,
  Switch,
  Alert,
} from 'react-native';
import { Picker } from '@react-native-picker/picker';
import DateTimePicker from '@react-native-community/datetimepicker';
import { Slider } from '@react-native-community/slider';
import { JobFilters, JobCategory, District } from '../types/Job';
import { ApiService } from '../services/ApiService';
import { colors, spacing, typography } from '../theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface FilterBottomSheetProps {
  visible: boolean;
  onClose: () => void;
  currentFilters: JobFilters;
  onApplyFilters: (filters: JobFilters) => void;
  onClearFilters: () => void;
}

export const FilterBottomSheet: React.FC<FilterBottomSheetProps> = ({
  visible,
  onClose,
  currentFilters,
  onApplyFilters,
  onClearFilters,
}) => {
  const [filters, setFilters] = useState<JobFilters>(currentFilters);
  const [categories, setCategories] = useState<JobCategory[]>([]);
  const [districts, setDistricts] = useState<District[]>([]);
  const [loading, setLoading] = useState(false);
  const [showDatePicker, setShowDatePicker] = useState<'from' | 'to' | null>(null);
  const [tempDate, setTempDate] = useState<Date>(new Date());

  // Load filter options
  useEffect(() => {
    if (visible) {
      loadFilterOptions();
    }
  }, [visible]);

  const loadFilterOptions = async () => {
    setLoading(true);
    try {
      const [categoriesRes, districtsRes] = await Promise.all([
        ApiService.getJobCategories(),
        ApiService.getDistricts(),
      ]);
      setCategories(categoriesRes.data);
      setDistricts(districtsRes.data);
    } catch (error) {
      console.error('Error loading filter options:', error);
      Alert.alert('Error', 'Failed to load filter options');
    } finally {
      setLoading(false);
    }
  };

  // Reset filters to current
  useEffect(() => {
    setFilters(currentFilters);
  }, [currentFilters]);

  const handleApplyFilters = () => {
    onApplyFilters(filters);
  };

  const handleClearAll = () => {
    const emptyFilters: JobFilters = {
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
    };
    setFilters(emptyFilters);
    onClearFilters();
  };

  const handleDateChange = (event: any, selectedDate?: Date) => {
    if (selectedDate) {
      const dateString = selectedDate.toISOString().split('T')[0];
      if (showDatePicker === 'from') {
        setFilters(prev => ({ ...prev, deadline_from: dateString }));
      } else if (showDatePicker === 'to') {
        setFilters(prev => ({ ...prev, deadline_to: dateString }));
      }
    }
    setShowDatePicker(null);
  };

  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Select Date';
    return new Date(dateString).toLocaleDateString();
  };

  const employmentStatusOptions = [
    { label: 'All', value: null },
    { label: 'Full-time', value: 'Full-time' },
    { label: 'Part-time', value: 'Part-time' },
    { label: 'Contract', value: 'Contract' },
    { label: 'Internship', value: 'Internship' },
    { label: 'Freelance', value: 'Freelance' },
  ];

  const workplaceOptions = [
    { label: 'All', value: null },
    { label: 'Onsite', value: 'Onsite' },
    { label: 'Remote', value: 'Remote' },
    { label: 'Hybrid', value: 'Hybrid' },
  ];

  const experienceLevelOptions = [
    { label: 'All', value: null },
    { label: 'Entry Level', value: 'Entry Level' },
    { label: 'Mid Level', value: 'Mid Level' },
    { label: 'Senior Level', value: 'Senior Level' },
    { label: 'Executive Level', value: 'Executive Level' },
  ];

  const educationLevelOptions = [
    { label: 'All', value: null },
    { label: 'Certificate', value: 'Certificate' },
    { label: 'Diploma', value: 'Diploma' },
    { label: 'Degree', value: 'Degree' },
    { label: 'Masters', value: 'Masters' },
    { label: 'PhD', value: 'PhD' },
  ];

  const sortOptions = [
    { label: 'Newest First', value: 'created_at', order: 'desc' },
    { label: 'Oldest First', value: 'created_at', order: 'asc' },
    { label: 'Deadline (Earliest)', value: 'deadline', order: 'asc' },
    { label: 'Deadline (Latest)', value: 'deadline', order: 'desc' },
    { label: 'Salary (Highest)', value: 'maximum_salary', order: 'desc' },
    { label: 'Salary (Lowest)', value: 'minimum_salary', order: 'asc' },
  ];

  return (
    <Modal
      visible={visible}
      animationType="slide"
      transparent={true}
      onRequestClose={onClose}
    >
      <View style={styles.overlay}>
        <View style={styles.container}>
          {/* Header */}
          <View style={styles.header}>
            <Text style={styles.title}>Filter Jobs</Text>
            <TouchableOpacity onPress={onClose}>
              <Icon name="close" size={24} color={colors.text} />
            </TouchableOpacity>
          </View>

          {/* Content */}
          <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
            {/* Job Category */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Job Category</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.category}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, category: value }))}
                  style={styles.picker}
                >
                  <Picker.Item label="All Categories" value={null} />
                  {categories.map(category => (
                    <Picker.Item
                      key={category.id}
                      label={category.name}
                      value={category.id}
                    />
                  ))}
                </Picker>
              </View>
            </View>

            {/* Location */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Location</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.district}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, district: value }))}
                  style={styles.picker}
                >
                  <Picker.Item label="All Locations" value={null} />
                  {districts.map(district => (
                    <Picker.Item
                      key={district.id}
                      label={district.name}
                      value={district.id}
                    />
                  ))}
                </Picker>
              </View>
            </View>

            {/* Employment Status */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Employment Type</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.employment_status}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, employment_status: value }))}
                  style={styles.picker}
                >
                  {employmentStatusOptions.map(option => (
                    <Picker.Item
                      key={option.value || 'all'}
                      label={option.label}
                      value={option.value}
                    />
                  ))}
                </Picker>
              </View>
            </View>

            {/* Workplace */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Workplace</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.workplace}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, workplace: value }))}
                  style={styles.picker}
                >
                  {workplaceOptions.map(option => (
                    <Picker.Item
                      key={option.value || 'all'}
                      label={option.label}
                      value={option.value}
                    />
                  ))}
                </Picker>
              </View>
            </View>

            {/* Experience Level */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Experience Level</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.experience_level}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, experience_level: value }))}
                  style={styles.picker}
                >
                  {experienceLevelOptions.map(option => (
                    <Picker.Item
                      key={option.value || 'all'}
                      label={option.label}
                      value={option.value}
                    />
                  ))}
                </Picker>
              </View>
            </View>

            {/* Education Level */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Education Level</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.education_level}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, education_level: value }))}
                  style={styles.picker}
                >
                  {educationLevelOptions.map(option => (
                    <Picker.Item
                      key={option.value || 'all'}
                      label={option.label}
                      value={option.value}
                    />
                  ))}
                </Picker>
              </View>
            </View>

            {/* Salary Range */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Salary Range</Text>
              <View style={styles.salaryContainer}>
                <View style={styles.salaryInputContainer}>
                  <Text style={styles.salaryLabel}>Min Salary</Text>
                  <TextInput
                    style={styles.salaryInput}
                    value={filters.salary_min?.toString() || ''}
                    onChangeText={(text) => setFilters(prev => ({ 
                      ...prev, 
                      salary_min: text ? parseInt(text) : null 
                    }))}
                    placeholder="0"
                    keyboardType="numeric"
                  />
                </View>
                <View style={styles.salaryInputContainer}>
                  <Text style={styles.salaryLabel}>Max Salary</Text>
                  <TextInput
                    style={styles.salaryInput}
                    value={filters.salary_max?.toString() || ''}
                    onChangeText={(text) => setFilters(prev => ({ 
                      ...prev, 
                      salary_max: text ? parseInt(text) : null 
                    }))}
                    placeholder="∞"
                    keyboardType="numeric"
                  />
                </View>
              </View>
            </View>

            {/* Age Range */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Age Range</Text>
              <View style={styles.ageContainer}>
                <View style={styles.ageSliderContainer}>
                  <Text style={styles.ageLabel}>Min Age: {filters.min_age || 18}</Text>
                  <Slider
                    style={styles.slider}
                    minimumValue={18}
                    maximumValue={65}
                    step={1}
                    value={filters.min_age || 18}
                    onValueChange={(value) => setFilters(prev => ({ ...prev, min_age: value }))}
                    minimumTrackTintColor={colors.primary}
                    maximumTrackTintColor={colors.lightGray}
                    thumbStyle={{ backgroundColor: colors.primary }}
                  />
                </View>
                <View style={styles.ageSliderContainer}>
                  <Text style={styles.ageLabel}>Max Age: {filters.max_age || 65}</Text>
                  <Slider
                    style={styles.slider}
                    minimumValue={18}
                    maximumValue={65}
                    step={1}
                    value={filters.max_age || 65}
                    onValueChange={(value) => setFilters(prev => ({ ...prev, max_age: value }))}
                    minimumTrackTintColor={colors.primary}
                    maximumTrackTintColor={colors.lightGray}
                    thumbStyle={{ backgroundColor: colors.primary }}
                  />
                </View>
              </View>
            </View>

            {/* Deadline Range */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Application Deadline</Text>
              <View style={styles.dateContainer}>
                <TouchableOpacity
                  style={styles.dateButton}
                  onPress={() => {
                    setTempDate(filters.deadline_from ? new Date(filters.deadline_from) : new Date());
                    setShowDatePicker('from');
                  }}
                >
                  <Text style={styles.dateButtonText}>
                    From: {formatDate(filters.deadline_from)}
                  </Text>
                  <Icon name="date-range" size={20} color={colors.primary} />
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.dateButton}
                  onPress={() => {
                    setTempDate(filters.deadline_to ? new Date(filters.deadline_to) : new Date());
                    setShowDatePicker('to');
                  }}
                >
                  <Text style={styles.dateButtonText}>
                    To: {formatDate(filters.deadline_to)}
                  </Text>
                  <Icon name="date-range" size={20} color={colors.primary} />
                </TouchableOpacity>
              </View>
            </View>

            {/* Industry */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Industry</Text>
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.textInput}
                  value={filters.industry || ''}
                  onChangeText={(text) => setFilters(prev => ({ ...prev, industry: text || null }))}
                  placeholder="e.g., Technology, Healthcare, Finance"
                  placeholderTextColor={colors.gray}
                />
              </View>
            </View>

            {/* Gender Requirement */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Gender Requirement</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={filters.gender}
                  onValueChange={(value) => setFilters(prev => ({ ...prev, gender: value }))}
                  style={styles.picker}
                >
                  <Picker.Item label="Any" value={null} />
                  <Picker.Item label="Male" value="Male" />
                  <Picker.Item label="Female" value="Female" />
                  <Picker.Item label="Other" value="Other" />
                </Picker>
              </View>
            </View>

            {/* Experience Field */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Experience Field</Text>
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.textInput}
                  value={filters.experience_field || ''}
                  onChangeText={(text) => setFilters(prev => ({ ...prev, experience_field: text || null }))}
                  placeholder="e.g., Software Development, Marketing, Sales"
                  placeholderTextColor={colors.gray}
                />
              </View>
            </View>

            {/* Company */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Company</Text>
              <View style={styles.inputContainer}>
                <TextInput
                  style={styles.textInput}
                  value={filters.company_id?.toString() || ''}
                  onChangeText={(text) => setFilters(prev => ({ 
                    ...prev, 
                    company_id: text ? parseInt(text) : null 
                  }))}
                  placeholder="Company ID or leave blank for all"
                  keyboardType="numeric"
                  placeholderTextColor={colors.gray}
                />
              </View>
            </View>

            {/* Special Requirements */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Special Requirements</Text>
              <View style={styles.switchContainer}>
                <Text style={styles.switchLabel}>Video CV Required</Text>
                <Switch
                  value={filters.required_video_cv === true}
                  onValueChange={(value) => setFilters(prev => ({ 
                    ...prev, 
                    required_video_cv: value ? true : null 
                  }))}
                  trackColor={{ false: colors.lightGray, true: colors.primary }}
                  thumbColor={colors.white}
                />
              </View>
            </View>

            {/* Sort Options */}
            <View style={styles.filterSection}>
              <Text style={styles.sectionTitle}>Sort By</Text>
              <View style={styles.pickerContainer}>
                <Picker
                  selectedValue={`${filters.sort_by}_${filters.sort_order}`}
                  onValueChange={(value) => {
                    const [sort_by, sort_order] = value.split('_');
                    setFilters(prev => ({ ...prev, sort_by, sort_order }));
                  }}
                  style={styles.picker}
                >
                  {sortOptions.map(option => (
                    <Picker.Item
                      key={`${option.value}_${option.order}`}
                      label={option.label}
                      value={`${option.value}_${option.order}`}
                    />
                  ))}
                </Picker>
              </View>
            </View>
          </ScrollView>

          {/* Footer */}
          <View style={styles.footer}>
            <TouchableOpacity style={styles.clearButton} onPress={handleClearAll}>
              <Text style={styles.clearButtonText}>Clear All</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.applyButton} onPress={handleApplyFilters}>
              <Text style={styles.applyButtonText}>Apply Filters</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>

      {/* Date Picker Modal */}
      {showDatePicker && (
        <DateTimePicker
          value={tempDate}
          mode="date"
          display="default"
          onChange={handleDateChange}
          minimumDate={new Date()}
        />
      )}
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  container: {
    backgroundColor: colors.white,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    maxHeight: '90%',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  title: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.text,
  },
  content: {
    flex: 1,
    paddingHorizontal: spacing.md,
  },
  filterSection: {
    marginVertical: spacing.md,
  },
  sectionTitle: {
    fontSize: typography.fontSize.md,
    fontWeight: typography.fontWeight.semibold,
    color: colors.text,
    marginBottom: spacing.sm,
  },
  pickerContainer: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    backgroundColor: colors.white,
  },
  picker: {
    height: 50,
  },
  inputContainer: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    backgroundColor: colors.white,
  },
  textInput: {
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.md,
    fontSize: typography.fontSize.md,
    color: colors.text,
    height: 50,
  },
  salaryContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  salaryInputContainer: {
    flex: 1,
    marginHorizontal: spacing.xs,
  },
  salaryLabel: {
    fontSize: typography.fontSize.sm,
    color: colors.gray,
    marginBottom: spacing.xs,
  },
  salaryInput: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.sm,
    fontSize: typography.fontSize.md,
    color: colors.text,
  },
  ageContainer: {
    marginVertical: spacing.sm,
  },
  ageSliderContainer: {
    marginBottom: spacing.md,
  },
  ageLabel: {
    fontSize: typography.fontSize.sm,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  slider: {
    width: '100%',
    height: 40,
  },
  dateContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  dateButton: {
    flex: 1,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 8,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.md,
    marginHorizontal: spacing.xs,
  },
  dateButtonText: {
    fontSize: typography.fontSize.sm,
    color: colors.text,
  },
  switchContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: spacing.sm,
  },
  switchLabel: {
    fontSize: typography.fontSize.md,
    color: colors.text,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  clearButton: {
    flex: 1,
    backgroundColor: colors.lightGray,
    paddingVertical: spacing.md,
    borderRadius: 8,
    marginRight: spacing.sm,
    alignItems: 'center',
  },
  clearButtonText: {
    fontSize: typography.fontSize.md,
    color: colors.text,
    fontWeight: typography.fontWeight.medium,
  },
  applyButton: {
    flex: 1,
    backgroundColor: colors.primary,
    paddingVertical: spacing.md,
    borderRadius: 8,
    marginLeft: spacing.sm,
    alignItems: 'center',
  },
  applyButtonText: {
    fontSize: typography.fontSize.md,
    color: colors.white,
    fontWeight: typography.fontWeight.medium,
  },
});
