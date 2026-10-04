import type { CommentVisibility, GlobalRole, MemberRole, SubmissionStatus } from './contracts.ts';
import type { MediaKind, SenseField, Vocabulary } from './fields.ts';
import type { WindowState } from './school-year.ts';

/** Read models returned by application services (JSON-serializable: dates are ISO strings). */

export interface UserView {
  id: string;
  displayName: string;
  firstName: string;
  lastName: string;
  email: string | null;
  globalRole: GlobalRole;
  avatar: string;
}

export interface VocabularyValue {
  id: string;
  vocabulary: Vocabulary;
  code: string;
  label: string;
  abbreviation: string | null;
  position: number;
  featured: boolean;
  active: boolean;
}

export interface ClassroomSettingsView {
  joinCode: string;
  schoolYear: number;
  validityYears: number;
  timeless: boolean;
  current: boolean;
  windowState: WindowState;
  studyLevelId: string | null;
  subjectId: string | null;
  groupLabel: string;
  maxSenses: number | null;
  visibleFields: SenseField[];
  requiredFields: SenseField[];
  guidelines: string;
  visibleToStudents: boolean;
  submissionsEnabled: boolean;
  submissionsStartAt: string | null;
  submissionsEndAt: string | null;
  commentsVisibility: CommentVisibility;
  commentsVisibleBefore: string | null;
}

export interface DictionaryView {
  id: string;
  kind: 'personal' | 'classroom';
  title: string;
  description: string;
  avatar: string | null;
  owner: { id: string; displayName: string };
  /** Role of the current user in a classroom; null for personal dictionaries. */
  myRole: MemberRole | null;
  entryCount: number;
  pendingCount: number;
  classroom: ClassroomSettingsView | null;
}

export interface MediaView {
  id: string;
  kind: MediaKind;
  mime: string;
  originalName: string;
  url: string;
}

export interface SenseView {
  id: string;
  position: number;
  definition: string;
  extraInfo: string;
  example: string;
  partOfSpeechId: string | null;
  genderId: string | null;
  numberId: string | null;
  languageId: string | null;
  foreignForm: string;
  hidden: boolean;
  topicIds: string[];
  media: MediaView[];
}

export interface SubmissionBrief {
  id: string;
  classroomId: string;
  classroomTitle: string;
  status: SubmissionStatus;
  submittedAt: string;
  reviewedAt: string | null;
  reviewNote: string;
}

export interface EntryView {
  id: string;
  dictionaryId: string;
  headword: string;
  initial: string;
  hidden: boolean;
  version: number;
  createdAt: string;
  updatedAt: string;
  author: { id: string; displayName: string } | null;
  sourceSubmissionId: string | null;
  senses: SenseView[];
  /** Personal entries: status of their submissions. */
  submissions: SubmissionBrief[];
}

export interface EntrySummary {
  id: string;
  headword: string;
  initial: string;
  hidden: boolean;
  senseCount: number;
  firstDefinition: string;
  updatedAt: string;
  submissions: Pick<SubmissionBrief, 'classroomTitle' | 'status'>[];
}

export interface EntrySnapshot {
  headword: string;
  senses: Omit<SenseView, 'id' | 'hidden'>[];
}

export interface SubmissionView extends SubmissionBrief {
  sourceEntryId: string;
  submittedBy: { id: string; displayName: string };
  reviewedBy: { id: string; displayName: string } | null;
  snapshot: EntrySnapshot;
  publishedEntryId: string | null;
  /** Published entry with the same headword from another source (blocks publication). */
  conflict: { entryId: string; headword: string } | null;
}

export interface MemberView {
  user: { id: string; displayName: string };
  role: MemberRole;
  active: boolean;
  isOwner: boolean;
  submissionCount: number;
}

export interface CommentView {
  id: string;
  classroomId: string;
  classroomTitle: string;
  author: { id: string; displayName: string };
  student: { id: string; displayName: string };
  submissionId: string | null;
  headword: string | null;
  body: string;
  createdAt: string;
}

export interface Page<T> {
  items: T[];
  total: number;
}

export interface AdminClassroomView {
  id: string;
  title: string;
  owner: string;
  schoolYear: number;
  validityYears: number;
  timeless: boolean;
  current: boolean;
  memberCount: number;
  entryCount: number;
}

export interface StatsView {
  bySchoolYear: {
    schoolYear: number;
    classrooms: number;
    current: number;
    members: number;
    published: number;
  }[];
  totals: {
    users: number;
    personalDictionaries: number;
    personalEntries: number;
    classrooms: number;
    submissions: number;
  };
}

export interface AuditEventView {
  id: string;
  actor: string | null;
  action: string;
  entityType: string;
  entityId: string | null;
  createdAt: string;
}
