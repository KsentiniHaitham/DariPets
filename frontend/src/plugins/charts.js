// Enregistrement des éléments Chart.js utilisés par le dashboard admin.
import {
  Chart,
  ArcElement,
  BarElement,
  PointElement,
  LineElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'

Chart.register(
  ArcElement,
  BarElement,
  PointElement,
  LineElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Filler,
)

// Palette de marque DariPets (violet/sarcelle moderne)
export const palette = {
  primary: '#7C3AED',
  primarySoft: 'rgba(124, 58, 237, 0.14)',
  secondary: '#14B8A6',
  accent: '#F59E0B',
  info: '#3B82F6',
  success: '#10B981',
  warning: '#F59E0B',
  error: '#EF4444',
  grey: '#9CA3AF',
}

export const statusColors = {
  pending: palette.warning,
  accepted: palette.info,
  paid: palette.success,
  completed: palette.secondary,
  cancelled: palette.grey,
  rejected: palette.error,
}
