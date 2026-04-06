import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Bell,
    CalendarClock,
    CalendarPlus,
    FileText,
    LayoutGrid,
    Settings,
    ShieldAlert,
    Users,
} from 'lucide-react';
import { index as adminStats } from '@/actions/App/Http/Controllers/Web/Admin/StatisticsController';
import { index as adminUsers } from '@/actions/App/Http/Controllers/Web/Admin/UserManagementController';
import { index as consultationsIndex } from '@/actions/App/Http/Controllers/Web/ConsultationController';
import { index as notificationsIndex } from '@/actions/App/Http/Controllers/Web/NotificationController';
import {
    create as patientConsultationCreate,
    index as patientConsultationsIndex,
} from '@/actions/App/Http/Controllers/Web/Patient/ConsultationController';
import { index as patientsIndex } from '@/actions/App/Http/Controllers/Web/PatientController';
import { index as reportsIndex } from '@/actions/App/Http/Controllers/Web/ReportController';
import { index as riskIndex } from '@/actions/App/Http/Controllers/Web/RiskController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const user = page.props.auth?.user as { role?: string } | undefined;
    const isAdmin = user?.role === 'admin';
    const isMedic = user?.role === 'medic' || isAdmin;
    const isPatient = user?.role === 'patient';

    const dashboardUrl = page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug)
        : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        ...(isMedic
            ? [
                  {
                      title: 'Pasien',
                      href: patientsIndex.url(),
                      icon: Users,
                  },
                  {
                      title: 'Konsultasi',
                      href: consultationsIndex.url(),
                      icon: CalendarClock,
                  },
                  {
                      title: 'Risiko Mental',
                      href: riskIndex.url(),
                      icon: ShieldAlert,
                  },
                  {
                      title: 'Notifikasi',
                      href: notificationsIndex.url(),
                      icon: Bell,
                  },
                  {
                      title: 'Laporan',
                      href: reportsIndex.url(),
                      icon: FileText,
                  },
              ]
            : isPatient
              ? [
                    {
                        title: 'Konsultasi Saya',
                        href: patientConsultationsIndex.url(),
                        icon: CalendarClock,
                    },
                    {
                        title: 'Buat Konsultasi',
                        href: patientConsultationCreate.url(),
                        icon: CalendarPlus,
                    },
                ]
              : []),
    ];

    const adminNavItems: NavItem[] = isAdmin
        ? [
              {
                  title: 'Manajemen Pengguna',
                  href: adminUsers.url(),
                  icon: Settings,
              },
              {
                  title: 'Statistik',
                  href: adminStats.url(),
                  icon: BarChart3,
              },
          ]
        : [];

    const footerNavItems: NavItem[] = [
        // {
        //     title: 'Repository',
        //     href: 'https://github.com/laravel/react-starter-kit',
        //     icon: FolderGit2,
        // },
        // {
        //     title: 'Documentation',
        //     href: 'https://laravel.com/docs/starter-kits#react',
        //     icon: BookOpen,
        // },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {adminNavItems.length > 0 && (
                    <NavMain items={adminNavItems} label="Admin" />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
