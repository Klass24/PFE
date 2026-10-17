/**
 * FleetControl UI Components
 * Export all UI components from a single entry point
 */

// Buttons
export * from './Button';
export { default as FCButton } from './Button';

// Badges/Chips
export * from './Badge';
export { default as FCBadge } from './Badge';

// Cards
export * from './Card';
export { default as FCCard } from './Card';

// Text Fields
export * from './TextField';
export { default as FCTextField } from './TextField';

// Logo
export * from './Logo';
export { default as FCLogo } from './Logo';

// Icons (re-exported from lucide-react for convenience)
export {
  // Navigation & Layout
  LayoutDashboard,
  Menu,
  X,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  ChevronUp,
  ArrowLeft,
  ArrowRight,
  
  // Fleet Management
  Truck,
  Car,
  Bus,
  Users,
  User,
  MapPin,
  Map,
  Navigation,
  Route,
  
  // Documents & Files
  FileText,
  Files,
  Upload,
  Download,
  Paperclip,
  
  // Actions
  Plus,
  Minus,
  Edit,
  Trash2,
  Search,
  Filter,
  RefreshCw,
  Save,
  
  // Status & Notifications
  Bell,
  AlertTriangle,
  AlertCircle,
  CheckCircle,
  XCircle,
  Info,
  Clock,
  Calendar,
  
  // Financial
  DollarSign,
  CreditCard,
  Receipt,
  TrendingUp,
  TrendingDown,
  
  // Fuel & Maintenance
  Fuel,
  Wrench,
  Settings,
  Gauge,
  
  // Communication
  Mail,
  Phone,
  MessageSquare,
  
  // Security & Auth
  Lock,
  Unlock,
  Shield,
  Key,
  Eye,
  EyeOff,
  LogIn,
  LogOut,
  
  // Miscellaneous
  Home,
  Building2,
  Star,
  Heart,
  Bookmark,
  Share2,
  Copy,
  MoreHorizontal,
  MoreVertical,
  ExternalLink,
  Loader2,
} from 'lucide-react';
