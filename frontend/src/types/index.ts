// Types globaux FleetControl

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message: string;
}

// ─── Dashboard ───────────────────────────────────────────────────────────────
export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  message: string;
}

// Auth
export interface AuthUser {
  uuid: string;
  name: string;
  email: string;
  role: 'admin' | 'manager' | 'driver' | 'super_admin';
  company_id: number;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

// Vehicle
export type VehicleStatus = 'available' | 'on_mission' | 'maintenance' | 'inactive';
export type FuelType = 'gasoline' | 'diesel' | 'electric' | 'hybrid';

export interface Vehicle {
  id?: number;
  uuid: string;
  plate_number: string;
  chassis_number: string | null;
  type: VehicleType;
  brand: string;
  model: string;
  year: number;
  mileage: number;
  fuel_type: FuelType;
  status: VehicleStatus;
  image_url: string | null;
  created_at: string;
}

export interface CreateVehicleDto {
  plate_number: string;
  brand: string;
  model: string;
  year: number;
  mileage?: number;
  fuel_type: FuelType;
  status?: VehicleStatus;
}

// Driver
export type DriverStatus = 'available' | 'on_mission' | 'inactive';

export interface Driver {
  id?: number;
  uuid: string;
  license_number: string;
  license_expiry: string;
  phone: string | null;
  status: DriverStatus;
  user?: {
    uuid: string;
    name: string;
    email: string;
  };
  created_at: string;
}

// Mission
export type MissionStatus = 'planned' | 'in_progress' | 'completed' | 'cancelled';

export interface Mission {
  uuid: string;
  origin: string;
  destination: string;
  planned_departure: string | null;
  planned_arrival: string | null;
  actual_departure: string | null;
  actual_arrival: string | null;
  start_date: string;
  end_date: string | null;
  status: MissionStatus;
  total_cost: number;
  notes: string | null;
  vehicle?: Partial<Vehicle>;
  driver?: {
    uuid: string;
    license_number: string;
    user?: { name: string };
  };
  checklist?: Checklist;
  expenses_count?: number;
  incidents_count?: number;
  created_at: string;
}

export interface CreateMissionDto {
  vehicle_id: number;
  driver_id: number;
  origin: string;
  destination: string;
  start_date: string;
  end_date?: string;
  planned_departure?: string;
  planned_arrival?: string;
  notes?: string;
}

// Expense
export type ExpenseType = 'fuel' | 'toll' | 'repair' | 'parking' | 'weighing' | 'other';

export interface CreateExpenseDto {
  mission_uuid: string;
  type: ExpenseType;
  amount: number;
  liters?: number;
  mileage?: number;
  notes?: string;
}

export interface Expense {
  uuid: string;
  type: ExpenseType;
  amount: number;
  liters: number | null;
  mileage: number | null;
  notes: string | null;
  receipt_url: string | null;
  mission_uuid: string;
  sync_status: SyncStatus;
  date_saisie_reelle: string | null;
  created_at: string;
}

// Incident
export type IncidentType = 'accident' | 'breakdown' | 'flat_tire' | 'traffic' | 'police_check' | 'mechanical_issue' | 'other';

export interface Incident {
  uuid: string;
  type: IncidentType;
  description: string;
  latitude: number | null;
  longitude: number | null;
  image_url: string | null;
  reported_at: string | null;
  sync_status: SyncStatus;
  created_at: string;
}

// Document
export type DocumentType = 'insurance' | 'registration' | 'license' | 'technical_control' | 'other';
export type DocumentableType = 'vehicle' | 'driver';

export interface CreateDocumentDto {
  documentable_type: DocumentableType;
  documentable_uuid: string;
  type: DocumentType;
  issue_date?: string;
  expiry_date?: string;
}

export interface Document {
  uuid: string;
  type: DocumentType;
  file_url: string;
  issue_date: string | null;
  expiry_date: string | null;
  alert_sent: boolean;
  is_expired: boolean;
  is_expiring_soon: boolean;
  documentable_type: string;
  documentable_uuid: string;
  created_at: string;
}

// Dashboard Stats
export interface DashboardStats {
  total_vehicles: number;
  available_vehicles: number;
  on_mission_vehicles: number;
  maintenance_vehicles: number;
  total_drivers: number;
  active_missions: number;
  planned_missions: number;
  total_expenses_month: number;
  expiring_documents_30: number;
  expired_documents: number;
  incidents_this_month: number;
}

// ─── Vehicle ──────────────────────────────────────────────────────────────────
export type VehicleType = 'car' | 'truck' | 'van' | 'motorcycle' | 'bus' | 'other';

export interface CreateVehicleDto {
  plate_number: string;
  chassis_number?: string;
  type?: VehicleType;
  brand: string;
  model: string;
  year: number;
  mileage?: number;
  fuel_type: FuelType;
  status?: VehicleStatus;
}

// ─── Checklist ────────────────────────────────────────────────────────────────
export type ChecklistStatus = 'draft' | 'submitted' | 'validated';
export type SyncStatus = 'synced' | 'pending';

export interface ChecklistItem {
  id: number;
  item_key: string;
  label: string;
  is_ok: boolean;
  is_required?: boolean;
  requires_photo: boolean;
  has_photo: boolean;
  note: string | null;
}

export interface Checklist {
  uuid: string;
  mission_id: string;
  status: ChecklistStatus;
  submitted_at: string | null;
  date_saisie_reelle: string | null;
  sync_status: SyncStatus;
  items: ChecklistItem[];
}

// ─── Notification ─────────────────────────────────────────────────────────────
export type NotificationType =
  | 'document_expiring'
  | 'incident_reported'
  | 'expense_threshold'
  | 'mission_started'
  | 'license_expired'
  | 'license_expiring';

export type NotificationChannel = 'dashboard' | 'email' | 'whatsapp';

export interface Notification {
  uuid: string;
  type: NotificationType;
  message: string;
  channel: NotificationChannel;
  is_read: boolean;
  sent_at: string | null;
  read_at: string | null;
  created_at: string;
}

// ─── Dashboard Charts ─────────────────────────────────────────────────────────
export interface VehicleExpenseStat {
  uuid: string;
  plate_number: string;
  brand: string;
  model: string;
  total: number;
}

export interface IncidentTypeStat {
  type: string;
  count: number;
}

export interface MissionCostStat {
  uuid: string;
  origin: string;
  destination: string;
  total_cost: number;
  start_date: string;
}

export interface ExpiringDocument {
  uuid: string;
  type: string;
  expiry_date: string;
  days_left: number;
  entity: string;
  entity_id: string;
  status: 'expired' | 'critical' | 'warning';
}

// ─── Subscription ─────────────────────────────────────────────────────────────
export interface Subscription {
  id: number;
  plan: 'starter' | 'pro' | 'enterprise';
  start_date: string;
  end_date: string | null;
  billing_status: 'active' | 'cancelled' | 'past_due' | 'trial';
}

// ─── User Management ──────────────────────────────────────────────────────────
export type UserRole = 'admin' | 'manager' | 'driver';

export interface User {
  uuid: string;
  name: string;
  email: string;
  role: UserRole;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface CreateUserDto {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: UserRole;
  is_active?: boolean;
}

// ─── Quota Usage ──────────────────────────────────────────────────────────────
export interface QuotaItem {
  used: number;
  max: number;
}

export interface QuotaUsage {
  plan: string;
  vehicles: QuotaItem;
  drivers: QuotaItem;
  users: QuotaItem;
}

// ─── API Token ────────────────────────────────────────────────────────────────
export interface ApiToken {
  uuid: string;
  name: string;
  abilities: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface CreateApiTokenDto {
  name: string;
  abilities?: string[];
  expires_at?: string;
}

// ─── Audit Log ────────────────────────────────────────────────────────────────
export interface AuditLog {
  uuid: string;
  action: string;
  auditable_type: string;
  auditable_id: number;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  user: { uuid: string; name: string } | null;
  ip_address: string | null;
  created_at: string;
}

// ─── Admin (Super Admin) ─────────────────────────────────────────────────────
export interface AdminCompany {
  uuid: string;
  name: string;
  slug: string;
  email: string | null;
  phone: string | null;
  plan: string;
  status: string;
  is_active: boolean;
  max_vehicles: number;
  max_drivers: number;
  max_users: number;
  users_count: number;
  vehicles_count: number;
  created_at: string;
}

export interface AdminStats {
  total_companies: number;
  active_companies: number;
  suspended_companies: number;
  total_users: number;
  companies_by_plan: Record<string, number>;
  recent_companies: { uuid: string; name: string; plan: string; status: string; created_at: string }[];
}

// ─── Register ─────────────────────────────────────────────────────────────────
export interface RegisterDto {
  company_name: string;
  company_email: string;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  plan?: string;
}

// ─── Maintenance ──────────────────────────────────────────────────────────────
export interface MaintenanceSchedule {
  uuid: string;
  vehicle: { uuid: string; plate_number: string; brand: string; model: string } | null;
  type: string;
  description: string | null;
  frequency_km: number | null;
  frequency_days: number | null;
  last_service_date: string | null;
  last_service_km: number | null;
  next_due_date: string | null;
  next_due_km: number | null;
  status: 'active' | 'overdue' | 'completed' | 'cancelled';
  created_at: string;
}

export interface MaintenanceRecord {
  uuid: string;
  schedule_uuid: string;
  service_date: string;
  mileage: number | null;
  cost: number | null;
  notes: string | null;
  performed_by: string | null;
  created_at: string;
}

export interface CompleteMaintenanceDto {
  service_date: string;
  mileage?: number;
  cost?: number;
  notes?: string;
  performed_by?: string;
}

// ─── Fuel Monitoring ──────────────────────────────────────────────────────────
export interface FuelEntry {
  uuid: string;
  vehicle: { uuid: string; plate_number: string } | null;
  mission: { uuid: string } | null;
  date: string;
  liters: number;
  cost: number;
  mileage: number;
  consumption_per_100km: number | null;
  station: string | null;
  receipt_image: string | null;
  is_anomaly: boolean;
  created_at: string;
}

export interface FuelAnomaly {
  uuid: string;
  fuel_entry_uuid: string;
  type: string;
  expected_value: number | null;
  actual_value: number | null;
  deviation_percent: number | null;
  status: 'detected' | 'reviewed' | 'resolved' | 'false_positive';
  resolved_by: string | null;
  resolved_at: string | null;
  notes: string | null;
  created_at: string;
}

export interface FuelAnalytics {
  total_liters: number;
  total_cost: number;
  avg_consumption: number;
  anomaly_count: number;
  entries_count: number;
}

// ─── Tracking & Geofencing ────────────────────────────────────────────────────
export interface TrackingPoint {
  uuid: string;
  mission_uuid: string;
  latitude: number;
  longitude: number;
  speed: number | null;
  heading: number | null;
  recorded_at: string;
}

export interface Geofence {
  uuid: string;
  name: string;
  type: 'circle' | 'polygon';
  center_lat: number | null;
  center_lng: number | null;
  radius_meters: number | null;
  polygon_points: Array<{ lat: number; lng: number }> | null;
  is_active: boolean;
  created_at: string;
}

// ─── Alert Rules ──────────────────────────────────────────────────────────────
export interface AlertRule {
  uuid: string;
  name: string;
  type: string;
  conditions: Record<string, unknown>;
  actions: string[];
  is_active: boolean;
  last_triggered_at: string | null;
  created_at: string;
}

// ─── Pricing / Plans ──────────────────────────────────────────────────────────
export interface Plan {
  uuid: string;
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number;
  currency: string;
  max_vehicles: number;
  max_drivers: number;
  max_users: number;
  features: string[];
  is_active: boolean;
  is_popular: boolean;
  sort_order: number;
  trial_days: number;
  is_free: boolean;
  yearly_saving: number;
  companies_count?: number;
}

export interface QuotaUsageItem {
  used: number;
  max: number;
  current_max?: number;
  target_max?: number;
}

export interface CurrentPlanDetails {
  plan: Plan | null;
  usage: {
    vehicles: QuotaUsageItem;
    drivers: QuotaUsageItem;
    users: QuotaUsageItem;
  };
  company: {
    uuid: string;
    name: string;
  };
}

export interface PlanComparison {
  current_plan: Plan | null;
  target_plan: Plan;
  usage: {
    vehicles: { used: number; current_max: number; target_max: number };
    drivers: { used: number; current_max: number; target_max: number };
    users: { used: number; current_max: number; target_max: number };
  };
  can_switch: boolean;
  is_upgrade: boolean;
  price_diff: {
    monthly: number;
    yearly: number;
  };
}

