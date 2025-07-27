import React from 'react';
import { TouchableOpacity, Text, StyleSheet } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { JobFilters } from '../types/Job';
import { colors, spacing, typography } from '../theme';

interface QuickFilterButtonProps {
  title: string;
  filters: Partial<JobFilters>;
  onPress?: () => void;
}

export const QuickFilterButton: React.FC<QuickFilterButtonProps> = ({
  title,
  filters,
  onPress,
}) => {
  const navigation = useNavigation();

  const handlePress = () => {
    if (onPress) {
      onPress();
    } else {
      navigation.navigate('JobListing', {
        defaultFilters: filters,
        title: title,
      });
    }
  };

  return (
    <TouchableOpacity style={styles.button} onPress={handlePress}>
      <Text style={styles.buttonText}>{title}</Text>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  button: {
    backgroundColor: colors.primary,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: 8,
    marginRight: spacing.sm,
    marginBottom: spacing.sm,
  },
  buttonText: {
    ...typography.button,
    color: 'white',
    textAlign: 'center',
  },
});

// Example usage component
export const JobCategoryFilters = () => {
  const quickFilters = [
    {
      title: 'Tech Jobs',
      filters: { category: '1', industry: 'technology' },
    },
    {
      title: 'Remote Work',
      filters: { workplace: 'remote' },
    },
    {
      title: 'Internships',
      filters: { employment_status: 'internship' },
    },
    {
      title: 'High Salary',
      filters: { salary_min: '5000000', sort: 'salary_desc' },
    },
    {
      title: 'Entry Level',
      filters: { experience_field: 'entry_level' },
    },
  ];

  return (
    <>
      {quickFilters.map((filter, index) => (
        <QuickFilterButton
          key={index}
          title={filter.title}
          filters={filter.filters}
        />
      ))}
    </>
  );
};

export default QuickFilterButton;
