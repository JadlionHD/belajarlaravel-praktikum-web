export const categories = [
  { id: 1, name: 'Jaringan' }, { id: 2, name: 'Perangkat' },
]
export const initialTickets = [
  { id: 1, subject: 'Wi-Fi putus', description: 'Koneksi terputus di kelas.',
    category_id: 1, status: 'open', is_urgent: true },
  { id: 2, subject: 'Proyektor redup', description: 'Tampilan sulit dibaca.',
    category_id: 2, status: 'pending', is_urgent: false },
  { id: 3, subject: 'Kabel LAN rusak', description: 'Kabel sudah diganti.',
    category_id: 1, status: 'closed', is_urgent: false },
]
