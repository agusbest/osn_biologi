import { lazy, Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'
import { AdminLayout, PublicLayout, RequireAuth, StudentLayout } from './components/Layouts'
import { Loading } from './components/ui'
import Landing from './pages/Landing'
import { Demo, Legal, NotFound } from './pages/Public'
import { ForgotPassword, Login, Register, ResetPassword } from './pages/AuthPages'

const Dashboard = lazy(() => import('./pages/student/Dashboard'))
const Curriculum = lazy(() => import('./pages/student/Curriculum'))
const LessonPage = lazy(() => import('./pages/student/LessonPage'))
const QuestionBank = lazy(() => import('./pages/student/QuestionBank'))
const AttemptRunner = lazy(() => import('./pages/student/AttemptRunner'))
const AttemptResult = lazy(() => import('./pages/student/AttemptResult'))
const Evaluation = lazy(() => import('./pages/student/Evaluation'))
const Profile = lazy(() => import('./pages/student/Profile'))
const Billing = lazy(() => import('./pages/student/Billing'))

const AdminHome = lazy(() => import('./pages/admin/AdminHome'))
const AdminLists = lazy(() => import('./pages/admin/AdminLists'))
const LessonEditor = lazy(() => import('./pages/admin/LessonEditor'))
const QuestionEditor = lazy(() => import('./pages/admin/QuestionEditor'))
const QuizEditor = lazy(() => import('./pages/admin/QuizEditor'))
const AdminPeople = lazy(() => import('./pages/admin/AdminPeople'))
const AdminSettings = lazy(() => import('./pages/admin/AdminSettings'))

export default function App() {
  return (
    <Suspense fallback={<Loading />}>
      <Routes>
        <Route element={<PublicLayout />}>
          <Route index element={<Landing />} />
          <Route path="demo" element={<Demo />} />
          <Route path="syarat-penggunaan" element={<Legal kind="syarat" />} />
          <Route path="kebijakan-privasi" element={<Legal kind="privasi" />} />
          <Route path="ketentuan-refund" element={<Legal kind="refund" />} />
          <Route path="masuk" element={<Login />} />
          <Route path="daftar" element={<Register />} />
          <Route path="lupa-password" element={<ForgotPassword />} />
          <Route path="reset-password" element={<ResetPassword />} />
          <Route path="*" element={<NotFound />} />
        </Route>

        <Route element={<RequireAuth />}>
          <Route path="app" element={<StudentLayout />}>
            <Route index element={<Dashboard />} />
            <Route path="kurikulum" element={<Curriculum />} />
            <Route path="materi/:id" element={<LessonPage />} />
            <Route path="latihan" element={<QuestionBank />} />
            <Route path="latihan/sesi/:id" element={<AttemptRunner />} />
            <Route path="latihan/hasil/:id" element={<AttemptResult />} />
            <Route path="evaluasi" element={<Evaluation />} />
            <Route path="paket" element={<Billing />} />
            <Route path="profil" element={<Profile />} />
          </Route>
        </Route>

        <Route element={<RequireAuth admin />}>
          <Route path="admin" element={<AdminLayout />}>
            <Route index element={<AdminHome />} />
            <Route path="topik" element={<AdminLists kind="topics" />} />
            <Route path="modul" element={<AdminLists kind="modules" />} />
            <Route path="materi" element={<AdminLists kind="lessons" />} />
            <Route path="materi/:id" element={<LessonEditor />} />
            <Route path="soal" element={<AdminLists kind="questions" />} />
            <Route path="soal/:id" element={<QuestionEditor />} />
            <Route path="kuis" element={<AdminLists kind="quizzes" />} />
            <Route path="kuis/:id" element={<QuizEditor />} />
            <Route path="siswa" element={<AdminPeople kind="users" />} />
            <Route path="pembayaran" element={<AdminPeople kind="payments" />} />
            <Route path="paket" element={<AdminPeople kind="plans" />} />
            <Route path="audit" element={<AdminPeople kind="audit" />} />
            <Route path="pengaturan" element={<AdminSettings />} />
          </Route>
        </Route>
      </Routes>
    </Suspense>
  )
}
