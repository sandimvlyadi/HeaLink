import { Form, Head } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { store } from '@/actions/App/Http/Controllers/Web/Patient/ConsultationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { UserProfile } from '@/types';

interface Medic {
    uuid: string;
    name: string;
    email: string;
    profile: UserProfile | null;
}

interface Props {
    medics: Medic[];
}

const minScheduledAt = new Date(Date.now() + 60 * 1000)
    .toISOString()
    .slice(0, 16);

export default function PatientConsultationsCreate({ medics }: Props) {
    return (
        <>
            <Head title="Buat Konsultasi — HeaLink" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Buat Konsultasi
                    </h1>
                    <p className="text-muted-foreground">
                        Pilih dokter dan jadwal konsultasi Anda
                    </p>
                </div>

                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CalendarClock className="size-4" />
                            Detail Konsultasi
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...store.form()}
                            options={{ preserveScroll: true }}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="medic_id">Dokter</Label>
                                        <Select name="medic_id" required>
                                            <SelectTrigger id="medic_id">
                                                <SelectValue placeholder="Pilih dokter..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {medics.map((m) => (
                                                    <SelectItem
                                                        key={m.uuid}
                                                        value={m.uuid}
                                                    >
                                                        {m.name}
                                                        {m.profile?.job
                                                            ? ` — ${m.profile.job}`
                                                            : ''}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.medic_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="scheduled_at">
                                            Tanggal &amp; Waktu
                                        </Label>
                                        <Input
                                            id="scheduled_at"
                                            type="datetime-local"
                                            name="scheduled_at"
                                            required
                                            min={minScheduledAt}
                                        />
                                        <InputError
                                            message={errors.scheduled_at}
                                        />
                                    </div>

                                    <Button type="submit" disabled={processing}>
                                        {processing
                                            ? 'Memproses...'
                                            : 'Buat Konsultasi'}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
