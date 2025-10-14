'use client'
import OpenJobs from './components/open-jobs'
import ApplicantStatus from './components/applicant-status'
import UpcommingInterviews from './components/upcomming-interviews'
import QuickActions from './components/quick-actions'
import EmployeeType from './components/employee-type'
import EmployeeStatus from './components/employee-status'
import ActionList from './components/action-list'
import YourPlan from './components/your-plan'
export default function Dashboard() {
    return (
        <div>
            <p
                className="text-[24px]/[30px] text-[#1C1C1C] font-semibold"
                id="dashboard-title"
                data-testid="dashboard-title"
            >
                Dashboard
            </p>
            <div className="mt-[24px] grid md:grid-cols-3  grid-cols-1 gap-[20px]">
                <OpenJobs />
                <ApplicantStatus />
                <UpcommingInterviews />
            </div>
            <div className="mt-[24px] grid md:grid-cols-[2fr_1fr_1fr] grid-cols-1 gap-[20px]">
                <QuickActions />
                <EmployeeType />
                <EmployeeStatus />
            </div>
            <div className="mt-[24px] grid md:grid-cols-2 grid-cols-1 gap-[20px]">
                <ActionList />
                <YourPlan />
            </div>
        </div>
    )
}