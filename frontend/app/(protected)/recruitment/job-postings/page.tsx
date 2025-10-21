'use client';

import { JSX, useEffect, useMemo, useState } from 'react';
import {
    ColumnDef,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    PaginationState,
    Row,
    RowSelectionState,
    SortingState,
    useReactTable,
} from '@tanstack/react-table';
import {
    ArrowRight,
    BriefcaseBusiness,
    ChevronDown,
    Clock,
    EllipsisVertical,
    LayoutGrid,
    LayoutList,
    ListFilter,
    MapPin,
    PenTool,
    PieChart,
    Plus,
    Search,
    Share2,
    Star,
    Users,
    X,
} from 'lucide-react';
import { toast } from 'sonner';
import { useCopyToClipboard } from '@/hooks/use-copy-to-clipboard';
import { Button } from '@/components/ui/button';

import { DataGrid, useDataGrid } from '@/components/ui/data-grid';
import { DataGridColumnHeader } from '@/components/ui/data-grid-column-header';
import { DataGridPagination } from '@/components/ui/data-grid-pagination';
import {
    DataGridTable,
    DataGridTableRowSelect,
    DataGridTableRowSelectAll,
} from '@/components/ui/data-grid-table';
import { Input } from '@/components/ui/input';
import { FilterTool } from './components/filter';
import { SelectedDialog } from './components/selectedDialog';
import { Alert, AlertIcon, AlertTitle } from '@/components/ui/alert';
import { RiCheckboxCircleFill } from '@remixicon/react';
import { DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger, DropdownMenu } from '@/components/ui/dropdown-menu';
import CheckDialog from './components/checkDialog';
import { NoData } from './components/noData';
import { useRouter } from 'next/navigation';
import { getJobPostings } from '@/api/job-posting';
import moment from 'moment';

/**
 * Interface representing job posting data structure
 */
/**
 * Mock job data for the component
 */
/**
 * JobPostings Component
 * 
 * Displays and manages a list of job postings with filtering, sorting, and pagination capabilities.
 * Supports both list and grid view modes.
 */
export default function JobPostings() {
    const [pagination, setPagination] = useState<PaginationState>({
        pageIndex: 0,
        pageSize: 10,
    });
    const [sorting, setSorting] = useState<SortingState>([
        { id: 'lastSession', desc: true },
    ]);
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({});
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedRows, setSelectedRows] = useState<string[]>([]);
    const [view, setView] = useState<'list' | 'grid'>('list');
    const [showFliter, setShowFilter] = useState(false);
    const [jobData, setJobData] = useState<any[]>([]);
    const [selectedLocations, setSelectedLocations] = useState<number[]>([]);
    const [selectedDepartments, setSelectedDepartments] = useState<number[]>([]);
    const [selectedTypes, setSelectedTypes] = useState<number[]>([]);
    const [selectedStatuses, setSelectedStatuses] = useState<string[]>([]);
    const router = useRouter();
    /**
     * Filters job data based on search query and selected statuses
     * @returns {IData[]} Filtered job data
     */
    const filteredData = useMemo<any[]>(() => {
        return jobData.filter((item) => {
            const matchesStatus =
                !selectedStatuses?.length ||
                selectedStatuses.includes(
                    item.status.replace('bg-', '').charAt(0).toUpperCase() +
                    item.status.replace('bg-', '').slice(1),
                );

            const searchLower = (searchQuery || "").toLowerCase();
            const matchesSearch =
                !searchQuery ||
                item.title.toLowerCase().includes(searchLower) ||
                (item.description || "").toLowerCase().includes(searchLower);

            return matchesSearch && matchesStatus;
        });
    }, [searchQuery, selectedStatuses, jobData]);

    const getData = async () => {
        try {
            const response = await getJobPostings({
                country_ids: selectedLocations,
                department_ids: selectedDepartments,
                employment_type_ids: selectedTypes,
                // status: selectedStatuses,
                page: pagination.pageIndex,
                per_page: pagination.pageSize,
            });
            console.log(response.data);

            setJobData(response.data);
        } catch (error) {
            console.log(error);
        }
    }

    useEffect(() => {
        getData();
    }, [selectedLocations, selectedDepartments, selectedTypes, selectedStatuses]);

    /**
     * Counts job postings by status
     * @returns {Record<string, number>} Object with status counts
     */
    const statusCounts = useMemo<Record<string, number>>(() => {
        return jobData.reduce(
            (acc, item) => {
                acc[item.status] = (acc[item.status] || 0) + 1;
                return acc;
            },
            {} as Record<string, number>,
        );
    }, []);

    /**
     * Defines columns for the data grid
     * @returns {ColumnDef<any>[]} Array of column definitions
     */
    const columns = useMemo<ColumnDef<any>[]>(
        () => [
            {
                id: 'select',
                header: () => <DataGridTableRowSelectAll />,
                cell: ({ row }) => <DataGridTableRowSelect row={row} />,
                enableSorting: false,
                enableHiding: false,
                enableResizing: false,
                size: 46,
                meta: {
                    cellClassName: '',
                },
            },
            {
                accessorKey: 'title',
                header: ({ column }) => (
                    <DataGridColumnHeader
                        className='text-[14px] font-medium'
                        title="Job Title"
                        column={column}
                        data-testid="job-title-header"
                    />
                ),
                cell: ({ row }) => (
                    <span
                        className="text-[14px] text-[#4b4b4b]"
                        data-testid={`job-title-${row.original.id}`}
                    >
                        {row.original.title}
                    </span>
                ),
                enableSorting: true,
                size: 220,
                meta: {
                    headerClassName: '',
                },
            },
            {
                accessorKey: 'department',
                header: ({ column }) => (
                    <DataGridColumnHeader
                        className='text-[14px] font-medium'
                        title="Department"
                        column={column}
                        data-testid="department-header"
                    />
                ),
                cell: ({ row }) => (
                    <span
                        className="text-[14px] text-[#4b4b4b]"
                        data-testid={`department-${row.original.id}`}
                    >
                        {row.original.department.name}
                    </span>
                ),
                enableSorting: true,
                size: 160,
                meta: {
                    headerClassName: '',
                },
            },
            {
                accessorKey: 'location',
                header: ({ column }) => (
                    <DataGridColumnHeader
                        className='text-[14px] font-medium'
                        title="Location"
                        column={column}
                        data-testid="location-header"
                    />
                ),
                cell: ({ row }) => (
                    <span
                        className="text-[14px] text-[#4b4b4b]"
                        data-testid={`location-${row.original.id}`}
                    >
                        {row.original.location_type.name}
                    </span>
                ),
                enableSorting: true,
                size: 180,
                meta: {
                    headerClassName: '',
                },
            },
            {
                accessorKey: 'applicants',
                header: ({ column }) => (
                    <DataGridColumnHeader
                        className='text-[14px] font-medium'
                        title="Applicants"
                        column={column}
                        data-testid="applicants-header"
                    />
                ),
                cell: ({ row }) => (
                    <span
                        className="text-[14px] text-[#4b4b4b]"
                        data-testid={`applicants-${row.original.id}`}
                    >
                        {row.original.applicants}
                    </span>
                ),
                enableSorting: true,
                size: 120,
                meta: {
                    headerClassName: '',
                },
            },
            {
                accessorKey: 'status',
                header: ({ column }) => (
                    <DataGridColumnHeader
                        className='text-[14px] font-medium'
                        title="Job Status"
                        column={column}
                        data-testid="status-header"
                    />
                ),
                cell: ({ row }) => {
                    let badgeClass = "bg-muted text-foreground";
                    if (row.original.status === "open") badgeClass = "bg-[#d6eeec] text-[#0D978B]";
                    else if (row.original.status === "closed") badgeClass = "bg-[#0D978B] text-white";
                    else if (row.original.status === "draft") badgeClass = "bg-[#e9e9e9] text-[#353535]";
                    return (
                        <span
                            className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${badgeClass}`}
                            data-testid={`status-badge-${row.original.id}`}
                        >
                            {row.original.status.charAt(0).toUpperCase() + row.original.status.slice(1)}
                        </span>
                    );
                },
                enableSorting: true,
                size: 100,
                meta: {
                    headerClassName: '',
                },
            },
            {
                accessorKey: 'due',
                header: ({ column }) => (
                    <DataGridColumnHeader
                        className='text-[14px] font-medium'
                        title="Due in"
                        column={column}
                        data-testid="due-header"
                    />
                ),
                cell: ({ row }) => (
                    <span
                        className="text-[14px] text-[#4b4b4b]"
                        data-testid={`due-${row.original.id}`}
                    >
                        {new Date(row.original.deadline) > new Date() ? moment(row.original.deadline).fromNow() : 'Expired'}
                    </span>
                ),
                enableSorting: true,
                size: 100,
                meta: {
                    headerClassName: '',
                },
            },
            {
                id: 'actions',
                header: '',
                cell: ({ row }) => <ActionsCell row={row} />,
                enableSorting: false,
                size: 60,
                meta: {
                    headerClassName: '',
                },
            },
        ],
        [],
    );

    useEffect(() => {
        setSelectedRows(Object.keys(rowSelection));
    }, [rowSelection]);

    const table = useReactTable({
        columns: columns as ColumnDef<any, any>[],
        data: filteredData,
        pageCount: Math.ceil((filteredData?.length || 0) / pagination.pageSize),
        getRowId: (row: any) => row.id,
        state: {
            pagination,
            sorting,
            rowSelection,
        },
        onPaginationChange: setPagination,
        onSortingChange: setSorting,
        onRowSelectionChange: setRowSelection,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
    });

    /**
     * ActionsCell Component
     * 
     * Renders action dropdown menu for a job posting row
     * @param {Object} props - Component props
     * @param {Row<IData>} props.row - Table row data
     * @returns {JSX.Element} Actions dropdown menu
     */
    function ActionsCell({ row }: { row: Row<any> }): JSX.Element {
        const { copyToClipboard } = useCopyToClipboard();

        /**
         * Copies job ID to clipboard and shows toast notification
         * @returns {void}
         */
        const handleCopyId = (): void => {
            copyToClipboard(String(row.original.id));
            const message = `Session ID successfully copied: ${row.original.id}`;
            toast.custom(
                (t) => (
                    <Alert
                        variant="mono"
                        icon="success"
                        close={false}
                        onClose={() => toast.dismiss(t)}
                    >
                        <AlertIcon>
                            <RiCheckboxCircleFill />
                        </AlertIcon>
                        <AlertTitle>{message}</AlertTitle>
                    </Alert>
                ),
                {
                    position: 'top-center',
                },
            );
        };

        return (
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        className="size-7"
                        mode="icon"
                        variant="ghost"
                        data-testid={`actions-button-${row.original.id}`}
                    >
                        <EllipsisVertical />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    side="bottom"
                    align="end"
                    data-testid={`actions-menu-${row.original.id}`}
                >
                    {row.original.status === "draft" &&
                        <div
                            className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                            data-testid={`edit-action-${row.original.id}`}
                            onClick={() => router.push(`/recruitment/job-postings/create-job?id=${row.original.id}`)}
                        >
                            Edit
                        </div>
                    }
                    <div
                        className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                        data-testid={`view-applicants-action-${row.original.id}`}
                    >
                        View Applicants
                    </div>
                    <div
                        className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                        data-testid={`duplicate-action-${row.original.id}`}
                    >
                        Duplicate
                    </div>
                    {row.original.status === "open" &&
                        <CheckDialog
                            action="close"
                            trigger={
                                <div
                                    className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                                    data-testid={`close-job-action-${row.original.id}`}
                                >
                                    Close Job
                                </div>
                            }
                        />
                    }
                    {row.original.status === "open" &&
                        <CheckDialog
                            action="unpublish"
                            trigger={
                                <div
                                    className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                                    data-testid={`unpublish-action-${row.original.id}`}
                                >
                                    Unpublish
                                </div>
                            }
                        />
                    }
                    <CheckDialog
                        action="delete"
                        trigger={
                            <div
                                className="cursor-pointer hover:bg-[#e9e9e9] text-[12px]/[18px] py-[7px] px-[12px] rounded-[8px]"
                                data-testid={`delete-action-${row.original.id}`}
                            >
                                Delete
                            </div>
                        }
                    />
                </DropdownMenuContent>
            </DropdownMenu>
        );
    }

    /**
     * Toolbar Component
     * 
     * Renders view controls and filter button
     * @param {Object} props - Component props
     * @param {'list' | 'grid'} props.view - Current view mode
     * @param {(view: 'list' | 'grid') => void} props.setView - View mode setter
     * @returns {JSX.Element} Toolbar controls
     */
    const Toolbar = ({ view, setView }: { view: 'list' | 'grid'; setView: (view: 'list' | 'grid') => void }): JSX.Element => {
        return (
            <div className='flex gap-[20px]'>
                <Button
                    variant="outline"
                    onClick={() => setShowFilter(!showFliter)}
                    className='text-[#053834] px-[12px] py-[6px] flex items-center gap-[6px] font-semibold'
                    data-testid="filter-button"
                >
                    <ListFilter className='size-[20px]' />
                    Filter
                </Button>
                <div className='flex h-[32px] bg-[#e9e9e9] rounded-[8px]'>
                    <div
                        className={`flex items-center justify-center h-full w-[32px] cursor-pointer rounded-[8px] ${view === 'list' ? 'bg-[#0D978B]' : ''}`}
                        data-testid="list-view-button"
                    >
                        <LayoutList
                            className={`${view === 'list' ? 'text-white' : 'text-[#4b4b4b]'} size-[20px]`}
                            onClick={() => setView('list')}
                        />
                    </div>
                    <div
                        className={`flex items-center justify-center h-full w-[32px] cursor-pointer rounded-[8px] ${view === 'grid' ? 'bg-[#0D978B]' : ''}`}
                        data-testid="grid-view-button"
                    >
                        <LayoutGrid
                            className={`${view === 'grid' ? 'text-white' : 'text-[#4b4b4b]'} size-[20px]`}
                            onClick={() => setView('grid')}
                        />
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className='w-full' data-testid="job-postings-container">
            <div className='flex items-center justify-between mb-[37px]'>
                <p
                    className='text-[24px]/[30px] font-semibold'
                    data-testid="page-title"
                >
                    Job Postings
                </p>
                <Button
                    className='h-[42px] font-semibold text-[14px]/[20px]'
                    data-testid="create-job-button"
                    onClick={() => { router.push('/recruitment/job-postings/create-job') }}
                >
                    <Plus className='size-[18px]' />
                    Create New Job
                </Button>
            </div>
            <DataGrid
                className='w-full'
                table={table}
                recordCount={filteredData?.length || 0}
                data-testid="job-postings-grid"
            >
                <div className="flex items-center justify-between">
                    <div className="relative">
                        <Search
                            className="size-4 text-muted-foreground absolute start-3 top-1/2 -translate-y-1/2"
                            data-testid="search-icon"
                        />
                        <Input
                            placeholder="Search Job"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="ps-9 w-40"
                            data-testid="search-input"
                        />
                        {searchQuery.length > 0 && (
                            <Button
                                mode="icon"
                                variant="ghost"
                                className="absolute end-1.5 top-1/2 -translate-y-1/2 h-6 w-6"
                                onClick={() => setSearchQuery('')}
                                data-testid="clear-search-button"
                            >
                                <X />
                            </Button>
                        )}
                    </div>

                    <Toolbar view={view} setView={setView} />
                </div>
                {showFliter && <FilterTool selectedLocations={selectedLocations} selectedDepartments={selectedDepartments} selectedTypes={selectedTypes} selectedStatuses={selectedStatuses} setSelectedLocations={setSelectedLocations} setSelectedDepartments={setSelectedDepartments} setSelectedTypes={setSelectedTypes} setSelectedStatuses={setSelectedStatuses} />}
                <div className='mt-[24px] w-full rounded-[12px] overflow-hidden relative'>
                    {view === 'list' &&
                        <> {filteredData.length === 0 ?
                            <NoData data-testid="no-data-message" /> : <>
                                {selectedRows.length > 0 &&
                                    <SelectedDialog
                                        selectedRows={selectedRows}
                                        totalCount={filteredData?.length}
                                        allData={filteredData}
                                        setSelectedRows={setSelectedRows}
                                        setRowSelection={setRowSelection}
                                        data-testid="selected-dialog"
                                    />
                                }
                                <div
                                    className='w-full overflow-x-auto h-[calc(100vh-300px)]'
                                    data-testid="list-view-container"
                                >
                                    <DataGridTable />
                                </div>
                                <DataGridPagination data-testid="pagination-controls" />
                            </>
                        }

                        </>
                    }
                    {view === 'grid' &&
                        <>
                            {filteredData.length === 0 &&
                                <NoData data-testid="no-data-message" />
                            }
                            {filteredData.length > 0 &&
                                <div
                                    className='w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 items-start gap-[20px] h-[calc(100vh-300px)] overflow-y-auto'
                                    data-testid="grid-view-container"
                                >
                                    {filteredData.map((item) => (
                                        <div
                                            key={item.id}
                                            className={`w-full bg-white flex flex-col rounded-[4px] border-t-[3px] ${item.status === 'Open' ? 'border-[#0D978B]' : item.status === 'Closed' ? 'border-[#A5A5A5]' : 'border-white'} px-[20px] py-[24px]`}
                                            data-testid={`job-card-${item.id}`}
                                        >
                                            <div className='w-full flex justify-between'>
                                                <span
                                                    className='text-[12px]/[22px] px-[8px] text-[#4B4B4B] bg-[#f9f9f9] rounded-[29px] flex items-center gap-[4px]'
                                                    data-testid={`department-badge-${item.id}`}
                                                >
                                                    {item.department.name === 'Data' && <PieChart className='text-[#00D47D] size-[16px]' />}
                                                    {item.department.name === 'Design' && <PenTool className='text-[#FFB547] size-[16px] rotate-[270deg]' />}
                                                    {item.department.name !== 'Data' && item.department.name !== 'Design' && <BriefcaseBusiness className='text-[#00D47D] size-[16px]' />}
                                                    {item.department.name}
                                                </span>
                                                <div className='flex gap-[14px]'>
                                                    <Button
                                                        variant="ghost"
                                                        className='cursor-pointer size-7'
                                                        data-testid={`share-button-${item.id}`}
                                                    >
                                                        <Share2 className=' text-[#8f8f8f]' />
                                                    </Button>
                                                    <ActionsCell row={table.getRow(item.id)} />
                                                </div>
                                            </div>
                                            <p
                                                className='mt-[10px] text-[18px]/[30px] font-semibold'
                                                data-testid={`job-title-${item.id}`}
                                            >
                                                {item.title}
                                            </p>
                                            <div className='mt-[4px] flex gap-[8px]'>
                                                <span
                                                    className='text-[12px]/[18px] px-[8px] flex items-center gap-[2px] text-[#787878]'
                                                    data-testid={`job-type-${item.id}`}
                                                >
                                                    <BriefcaseBusiness className='size-[16px]' />
                                                    Fulltime
                                                </span>
                                                <span
                                                    className='text-[12px]/[18px] px-[8px] flex items-center gap-[2px] text-[#787878]'
                                                    data-testid={`job-location-${item.id}`}
                                                >
                                                    <MapPin className='size-[16px]' />
                                                    {item.location_type.name}
                                                </span>
                                            </div>
                                            {item.status !== 'Draft' ? (
                                                <div
                                                    className='mt-[14px] w-full flex rounded-[6px] border border-[#e9e9e9] pl-[14px] pr-[8px] py-[8px]'
                                                    data-testid={`stats-container-${item.id}`}
                                                >
                                                    <div className='w-1/2 border-r border-[#e9e9e9]'>
                                                        <div className='flex gap-[4px]'>
                                                            <Users className='size-[20px] text-[#4b4b4b]' />
                                                            <span
                                                                className='text-[16px]/[22px] font-semibold text-[#4b4b4b]'
                                                                data-testid={`applicants-count-${item.id}`}
                                                            >
                                                                {item.applicants}
                                                            </span>
                                                        </div>
                                                        <p className='text-[12px]/[22px] text-[#8f8f8f]'>Candidates Applied</p>
                                                    </div>
                                                    <div className='w-1/2 pl-[14px]'>
                                                        <div className='flex gap-[4px]'>
                                                            <Star className='size-[20px] text-[#4b4b4b]' />
                                                            <span className='text-[16px]/[22px] font-semibold text-[#4b4b4b]'>10</span>
                                                        </div>
                                                        <p className='text-[12px]/[22px] text-[#8f8f8f]'>Shortlisted</p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div
                                                    className='mt-[24px] mb-[24px] text-[#bdbdbd] text-[16px] flex-1 flex items-center justify-center'
                                                    data-testid={`draft-message-${item.id}`}
                                                >
                                                    Complete the job details to publish
                                                </div>
                                            )}
                                            {item.status !== 'Draft' && (
                                                <div
                                                    className='mt-[8px] flex gap-[4px]'
                                                    data-testid={`due-date-${item.id}`}
                                                >
                                                    <Clock className='size-[16px] text-[#8f8f8f]' />
                                                    <span className='text-[12px]/[18px] text-[#8f8f8f]'>{item.due} remaining</span>
                                                </div>
                                            )}
                                            <div className='mt-[19px] flex justify-between w-full'>
                                                {item.status === 'Open' && (
                                                    <Button
                                                        className='h-[24px] rounded-full bg-[#0D978B] hover:bg-[#0D978B]'
                                                        data-testid={`status-button-${item.id}`}
                                                    >
                                                        <span className='text-[12px]/[22px] text-white'>Open</span>
                                                        <ChevronDown className='size-[12px] text-white' />
                                                    </Button>
                                                )}
                                                {item.status === 'Closed' && (
                                                    <Button
                                                        className='h-[24px] rounded-full bg-[#353535] text-white hover:bg-[#e9e9e9]'
                                                        data-testid={`status-button-${item.id}`}
                                                    >
                                                        <span className='text-[12px]/[22px]'>Closed</span>
                                                        <ChevronDown className='size-[12px]' />
                                                    </Button>
                                                )}
                                                {item.status === 'Draft' && (
                                                    <Button
                                                        className='h-[24px] rounded-full bg-[#e9e9e9] text-[#353535] hover:bg-[#e9e9e9]'
                                                        data-testid={`status-button-${item.id}`}
                                                    >
                                                        <span className='text-[12px]/[22px]'>Draft</span>
                                                    </Button>
                                                )}
                                                {item.status !== 'Draft' ? (
                                                    <button
                                                        className='text-[#0d978b] flex items-center gap-[2px]'
                                                        data-testid={`view-details-${item.id}`}
                                                    >
                                                        <span className='text-[14px]/[22px] font-medium'>View Details</span>
                                                        <ArrowRight className='size-[16px]' />
                                                    </button>
                                                ) : (
                                                    <button
                                                        className='text-[#0d978b] flex items-center gap-[2px]'
                                                        data-testid={`continue-edit-${item.id}`}
                                                    >
                                                        <span className='text-[14px]/[22px] font-medium'>Continue Edit</span>
                                                        <ArrowRight className='size-[16px]' />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            }
                        </>
                    }
                </div>
            </DataGrid>
        </div>
    );
};