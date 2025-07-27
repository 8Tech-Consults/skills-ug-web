import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Image,
} from 'react-native';
import { Job } from '../types/Job';
import { colors, spacing, typography } from '../theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface JobCardProps {
  job: Job;
  onPress: () => void;
  onBookmark: () => void;
  isBookmarked?: boolean;
}

export const JobCard: React.FC<JobCardProps> = ({ 
  job, 
  onPress, 
  onBookmark, 
  isBookmarked = false 
}) => {
  const formatSalary = (min: number | null, max: number | null) => {
    if (!min && !max) return 'Salary not specified';
    if (!min) return `Up to ${max?.toLocaleString()}`;
    if (!max) return `From ${min?.toLocaleString()}`;
    return `${min?.toLocaleString()} - ${max?.toLocaleString()}`;
  };

  const formatDeadline = (deadline: string) => {
    const deadlineDate = new Date(deadline);
    const today = new Date();
    const timeDiff = deadlineDate.getTime() - today.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    if (daysDiff < 0) return 'Expired';
    if (daysDiff === 0) return 'Today';
    if (daysDiff === 1) return 'Tomorrow';
    if (daysDiff <= 7) return `${daysDiff} days left`;
    return deadlineDate.toLocaleDateString();
  };

  const getUrgencyColor = (deadline: string) => {
    const deadlineDate = new Date(deadline);
    const today = new Date();
    const timeDiff = deadlineDate.getTime() - today.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
    
    if (daysDiff < 0) return colors.error;
    if (daysDiff <= 3) return colors.warning;
    if (daysDiff <= 7) return colors.info;
    return colors.success;
  };

  return (
    <TouchableOpacity style={styles.container} onPress={onPress} activeOpacity={0.7}>
      <View style={styles.card}>
        {/* Header */}
        <View style={styles.header}>
          <View style={styles.companyInfo}>
            {job.company_logo && (
              <Image source={{ uri: job.company_logo }} style={styles.companyLogo} />
            )}
            <View style={styles.companyDetails}>
              <Text style={styles.jobTitle} numberOfLines={2}>
                {job.title}
              </Text>
              <Text style={styles.companyName} numberOfLines={1}>
                {job.company_name || 'Company Name'}
              </Text>
            </View>
          </View>
          <TouchableOpacity onPress={onBookmark} style={styles.bookmarkButton}>
            <Icon
              name={isBookmarked ? 'bookmark' : 'bookmark-border'}
              size={24}
              color={isBookmarked ? colors.primary : colors.gray}
            />
          </TouchableOpacity>
        </View>

        {/* Job Details */}
        <View style={styles.details}>
          <View style={styles.detailRow}>
            <Icon name="location-on" size={16} color={colors.gray} />
            <Text style={styles.detailText}>
              {job.district_text || job.address || 'Location not specified'}
            </Text>
          </View>
          
          <View style={styles.detailRow}>
            <Icon name="work" size={16} color={colors.gray} />
            <Text style={styles.detailText}>
              {job.employment_status || 'Full-time'}
            </Text>
          </View>

          <View style={styles.detailRow}>
            <Icon name="computer" size={16} color={colors.gray} />
            <Text style={styles.detailText}>
              {job.workplace || 'Onsite'}
            </Text>
          </View>

          {(job.minimum_salary || job.maximum_salary) && (
            <View style={styles.detailRow}>
              <Icon name="attach-money" size={16} color={colors.gray} />
              <Text style={styles.detailText}>
                {formatSalary(job.minimum_salary, job.maximum_salary)}
              </Text>
            </View>
          )}
        </View>

        {/* Tags */}
        <View style={styles.tags}>
          {job.category_text && (
            <View style={styles.tag}>
              <Text style={styles.tagText}>{job.category_text}</Text>
            </View>
          )}
          {job.vacancies_count && job.vacancies_count > 1 && (
            <View style={styles.tag}>
              <Text style={styles.tagText}>{job.vacancies_count} positions</Text>
            </View>
          )}
          {job.required_video_cv && (
            <View style={[styles.tag, styles.videoTag]}>
              <Icon name="videocam" size={12} color={colors.white} />
              <Text style={[styles.tagText, styles.videoTagText]}>Video CV</Text>
            </View>
          )}
        </View>

        {/* Footer */}
        <View style={styles.footer}>
          <View style={styles.deadlineContainer}>
            <Icon name="schedule" size={16} color={getUrgencyColor(job.deadline)} />
            <Text style={[styles.deadlineText, { color: getUrgencyColor(job.deadline) }]}>
              {formatDeadline(job.deadline)}
            </Text>
          </View>
          
          <View style={styles.postedDate}>
            <Text style={styles.postedText}>
              Posted {new Date(job.created_at).toLocaleDateString()}
            </Text>
          </View>
        </View>

        {/* Requirements Preview */}
        {job.minimum_academic_qualification && (
          <View style={styles.requirements}>
            <Text style={styles.requirementsTitle}>Requirements:</Text>
            <Text style={styles.requirementsText} numberOfLines={2}>
              {job.minimum_academic_qualification}
              {job.experience_period && ` • ${job.experience_period} experience`}
            </Text>
          </View>
        )}
      </View>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  container: {
    marginVertical: spacing.sm,
  },
  card: {
    backgroundColor: colors.white,
    borderRadius: 12,
    padding: spacing.md,
    shadowColor: colors.shadow,
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
    borderWidth: 1,
    borderColor: colors.border,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: spacing.md,
  },
  companyInfo: {
    flexDirection: 'row',
    flex: 1,
    alignItems: 'flex-start',
  },
  companyLogo: {
    width: 48,
    height: 48,
    borderRadius: 8,
    marginRight: spacing.sm,
    backgroundColor: colors.lightGray,
  },
  companyDetails: {
    flex: 1,
  },
  jobTitle: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  companyName: {
    fontSize: typography.fontSize.md,
    color: colors.gray,
  },
  bookmarkButton: {
    padding: spacing.xs,
    marginLeft: spacing.sm,
  },
  details: {
    marginBottom: spacing.md,
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: spacing.xs,
  },
  detailText: {
    fontSize: typography.fontSize.sm,
    color: colors.text,
    marginLeft: spacing.sm,
    flex: 1,
  },
  tags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginBottom: spacing.md,
  },
  tag: {
    backgroundColor: colors.lightGray,
    paddingHorizontal: spacing.sm,
    paddingVertical: spacing.xs,
    borderRadius: 16,
    marginRight: spacing.sm,
    marginBottom: spacing.xs,
  },
  tagText: {
    fontSize: typography.fontSize.xs,
    color: colors.text,
    fontWeight: typography.fontWeight.medium,
  },
  videoTag: {
    backgroundColor: colors.primary,
    flexDirection: 'row',
    alignItems: 'center',
  },
  videoTagText: {
    color: colors.white,
    marginLeft: spacing.xs,
  },
  footer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  deadlineContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  deadlineText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    marginLeft: spacing.xs,
  },
  postedDate: {
    alignItems: 'flex-end',
  },
  postedText: {
    fontSize: typography.fontSize.xs,
    color: colors.gray,
  },
  requirements: {
    marginTop: spacing.sm,
    paddingTop: spacing.sm,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  requirementsTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.text,
    marginBottom: spacing.xs,
  },
  requirementsText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray,
    lineHeight: 18,
  },
});
