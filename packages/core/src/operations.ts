import { z } from 'zod';
import {
  ClassroomInput,
  CommentInput,
  EntryInput,
  GLOBAL_ROLES,
  JoinInput,
  ListEntriesQuery,
  LoginInput,
  MEMBER_ROLES,
  SUBMISSION_STATUSES,
  VocabularyValueInput,
} from './contracts.ts';
import type {
  AdminClassroomView,
  AuditEventView,
  CommentView,
  DictionaryView,
  EntrySummary,
  EntryView,
  MemberView,
  Page,
  StatsView,
  SubmissionBrief,
  SubmissionView,
  UserView,
  VocabularyValue,
} from './types.ts';

/**
 * Single source of truth for the application API. The Fastify server registers one REST route per
 * operation, the HTTP client builds requests from it, and the demo calls the same services in-process.
 * Path params (`:name`) are taken from input fields of the same name; GET/DELETE send the rest as query.
 */

const id = z.uuid();
const empty = z.object({});
type Ok = { ok: true };

const op = <I extends z.ZodType>(
  method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
  path: string,
  input: I,
) => ({ method, path, input }) as const;

export const operations = {
  me: op('GET', '/api/me', empty),
  login: op('POST', '/api/auth/login', LoginInput),
  logout: op('POST', '/api/auth/logout', empty),
  vocabularies: op('GET', '/api/vocabularies', empty),

  myDictionary: op('GET', '/api/me/dictionary', empty),
  updateMyDictionary: op(
    'PATCH',
    '/api/me/dictionary',
    z.object({
      title: z.string().trim().min(1).max(150),
      avatar: z.string().regex(/^[a-z0-9-]{1,40}$/),
    }),
  ),
  myClassrooms: op('GET', '/api/classrooms', empty),
  getDictionary: op('GET', '/api/dictionaries/:dictionaryId', z.object({ dictionaryId: id })),
  listEntries: op('GET', '/api/dictionaries/:dictionaryId/entries', ListEntriesQuery),
  exportDictionary: op(
    'GET',
    '/api/dictionaries/:dictionaryId/export',
    z.object({ dictionaryId: id, includeHidden: z.coerce.boolean().default(false) }),
  ),

  getEntry: op('GET', '/api/entries/:entryId', z.object({ entryId: id })),
  createEntry: op(
    'POST',
    '/api/dictionaries/:dictionaryId/entries',
    z.object({ dictionaryId: id, entry: EntryInput }),
  ),
  updateEntry: op(
    'PUT',
    '/api/entries/:entryId',
    z.object({ entryId: id, version: z.number().int().min(1), entry: EntryInput }),
  ),
  deleteEntry: op('DELETE', '/api/entries/:entryId', z.object({ entryId: id })),
  setEntryHidden: op(
    'PATCH',
    '/api/entries/:entryId/visibility',
    z.object({ entryId: id, hidden: z.boolean() }),
  ),
  setSenseHidden: op(
    'PATCH',
    '/api/senses/:senseId/visibility',
    z.object({ senseId: id, hidden: z.boolean() }),
  ),

  submitEntries: op(
    'POST',
    '/api/submissions',
    z.object({
      entryIds: z.array(id).min(1).max(500),
      classroomIds: z.array(id).min(1).max(20),
      dryRun: z.boolean().default(false),
    }),
  ),
  withdrawSubmission: op(
    'POST',
    '/api/submissions/:submissionId/withdraw',
    z.object({ submissionId: id }),
  ),
  listSubmissions: op(
    'GET',
    '/api/classrooms/:classroomId/submissions',
    z.object({
      classroomId: id,
      status: z.enum(SUBMISSION_STATUSES).optional(),
      studentId: id.optional(),
      q: z.string().trim().max(150).optional(),
    }),
  ),
  getSubmission: op('GET', '/api/submissions/:submissionId', z.object({ submissionId: id })),
  publishSubmissions: op(
    'POST',
    '/api/submissions/publish',
    z.object({ submissionIds: z.array(id).min(1).max(200) }),
  ),
  rejectSubmission: op(
    'POST',
    '/api/submissions/:submissionId/reject',
    z.object({ submissionId: id, note: z.string().trim().max(1000).default('') }),
  ),

  createClassroom: op('POST', '/api/classrooms', ClassroomInput),
  updateClassroom: op(
    'PUT',
    '/api/classrooms/:classroomId',
    z.object({ classroomId: id, classroom: ClassroomInput }),
  ),
  deleteClassroom: op('DELETE', '/api/classrooms/:classroomId', z.object({ classroomId: id })),
  joinClassroom: op('POST', '/api/classrooms/join', JoinInput),
  regenerateJoinCode: op(
    'POST',
    '/api/classrooms/:classroomId/join-code',
    z.object({ classroomId: id }),
  ),
  listMembers: op('GET', '/api/classrooms/:classroomId/members', z.object({ classroomId: id })),
  updateMember: op(
    'PATCH',
    '/api/classrooms/:classroomId/members/:userId',
    z.object({
      classroomId: id,
      userId: id,
      role: z.enum(MEMBER_ROLES).optional(),
      active: z.boolean().optional(),
    }),
  ),

  listComments: op(
    'GET',
    '/api/comments',
    z.object({ classroomId: id.optional(), studentId: id.optional() }),
  ),
  createComment: op('POST', '/api/comments', CommentInput),
  deleteComment: op('DELETE', '/api/comments/:commentId', z.object({ commentId: id })),

  adminListUsers: op(
    'GET',
    '/api/admin/users',
    z.object({ q: z.string().trim().max(100).optional() }),
  ),
  adminSetUserRole: op(
    'PATCH',
    '/api/admin/users/:userId',
    z.object({ userId: id, globalRole: z.enum(GLOBAL_ROLES) }),
  ),
  adminListClassrooms: op(
    'GET',
    '/api/admin/classrooms',
    z.object({ filter: z.enum(['all', 'current', 'expired', 'timeless']).default('all') }),
  ),
  adminUpdateValidity: op(
    'PATCH',
    '/api/admin/classrooms/:classroomId',
    z.object({
      classroomId: id,
      timeless: z.boolean().optional(),
      reactivate: z.boolean().optional(),
    }),
  ),
  adminStats: op('GET', '/api/admin/stats', empty),
  adminAudit: op(
    'GET',
    '/api/admin/audit',
    z.object({ limit: z.coerce.number().int().min(1).max(500).default(100) }),
  ),
  adminSaveVocabularyValue: op(
    'PUT',
    '/api/admin/vocabularies',
    VocabularyValueInput.extend({ id: id.optional() }),
  ),
} as const;

export type Operations = typeof operations;
export type OperationName = keyof Operations;
export type OperationInput<K extends OperationName> = z.input<Operations[K]['input']>;
export type ParsedInput<K extends OperationName> = z.output<Operations[K]['input']>;

export interface SubmitResult {
  created: SubmissionBrief[];
  problems: {
    entryId: string;
    headword: string;
    classroomId: string;
    classroomTitle: string;
    message: string;
  }[];
}
export interface PublishResult {
  published: string[];
  conflicts: { submissionId: string; headword: string; message: string }[];
}

export interface OperationOutputs {
  me: { user: UserView | null };
  login: UserView;
  logout: Ok;
  vocabularies: VocabularyValue[];
  myDictionary: DictionaryView;
  updateMyDictionary: DictionaryView;
  myClassrooms: DictionaryView[];
  getDictionary: DictionaryView;
  listEntries: Page<EntrySummary>;
  exportDictionary: { dictionary: DictionaryView; entries: EntryView[] };
  getEntry: EntryView;
  createEntry: EntryView;
  updateEntry: EntryView;
  deleteEntry: Ok;
  setEntryHidden: EntryView;
  setSenseHidden: EntryView;
  submitEntries: SubmitResult;
  withdrawSubmission: Ok;
  listSubmissions: SubmissionView[];
  getSubmission: SubmissionView;
  publishSubmissions: PublishResult;
  rejectSubmission: Ok;
  createClassroom: DictionaryView;
  updateClassroom: DictionaryView;
  deleteClassroom: Ok;
  joinClassroom: DictionaryView;
  regenerateJoinCode: DictionaryView;
  listMembers: MemberView[];
  updateMember: MemberView[];
  listComments: CommentView[];
  createComment: CommentView;
  deleteComment: Ok;
  adminListUsers: UserView[];
  adminSetUserRole: UserView;
  adminListClassrooms: AdminClassroomView[];
  adminUpdateValidity: AdminClassroomView;
  adminStats: StatsView;
  adminAudit: AuditEventView[];
  adminSaveVocabularyValue: VocabularyValue;
}

/** What both the HTTP client and the in-browser demo client implement. */
export type LexicanApi = {
  [K in OperationName]: (input: OperationInput<K>) => Promise<OperationOutputs[K]>;
} & {
  uploadMedia(file: File | Blob, name: string): Promise<import('./types.ts').MediaView>;
};

/** Fill `:params` in a path from the input object; returns the path and the remaining fields. */
export function bindPath(
  path: string,
  input: Record<string, unknown>,
): { url: string; rest: Record<string, unknown> } {
  const used = new Set<string>();
  const url = path.replace(/:(\w+)/g, (_, name: string) => {
    used.add(name);
    return encodeURIComponent(String(input[name]));
  });
  const rest = Object.fromEntries(Object.entries(input).filter(([k]) => !used.has(k)));
  return { url, rest };
}
