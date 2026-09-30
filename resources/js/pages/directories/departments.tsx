import { DirectoryManager } from '@/components/directory-manager';
import { type PickablePerson } from '@/components/person-picker';
import DirectoriesLayout from '@/layouts/directories-layout';

interface DepartmentItem {
    id: number;
    name: string;
    parent_id: number | null;
    users_count: number;
    total_count: number;
    heads: { id: number; name: string }[];
    member_ids: number[];
}

export default function Departments({ items, employees, canEdit }: { items: DepartmentItem[]; employees: PickablePerson[]; canEdit: boolean }) {
    return (
        <DirectoriesLayout title="Отделы">
            <DirectoryManager
                items={items.map((item) => ({
                    id: item.id,
                    label: item.name,
                    users_count: item.users_count,
                    total_count: item.total_count,
                    parent_id: item.parent_id,
                    heads: item.heads,
                    member_ids: item.member_ids,
                }))}
                canEdit={canEdit}
                field="name"
                route="directories.departments"
                labels={{ add: 'Добавить отдел', create: 'Новый отдел', edit: 'Изменить отдел', accusative: 'отдел' }}
                employeesUrl={(item) => route('employees.index', { department: [item.id] })}
                tree
                people={employees}
            />
        </DirectoriesLayout>
    );
}
