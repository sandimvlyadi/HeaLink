export type UserRole = 'patient' | 'medic' | 'admin';

export type RiskLevel = 'low' | 'medium' | 'high' | 'critical';

export type ConsultationStatus =
    | 'pending'
    | 'ongoing'
    | 'completed'
    | 'cancelled';

export type NotificationType = 'info' | 'warning' | 'critical' | 'reminder';

export type MoodType = 'very_bad' | 'bad' | 'neutral' | 'good' | 'very_good';

export type UserProfile = {
    uuid: string;
    gender: string | null;
    dob: string | null;
    job: string | null;
    phone: string | null;
    avatar_path: string | null;
    bio: string | null;
};

export type MentalStatusLog = {
    uuid: string;
    risk_level: RiskLevel;
    risk_score: number;
    detected_emotion: string | null;
    summary_note: string | null;
    contributing_factors: Record<string, number | null> | null;
    created_at: string;
};

export type WearableData = {
    uuid: string;
    hrv_score: number | null;
    heart_rate: number | null;
    stress_index: number | null;
    device_type: string | null;
    is_simulated: boolean;
    recorded_at: string;
};

export type HealthScreening = {
    uuid: string;
    height_cm: number | null;
    weight_kg: number | null;
    bmi: number | null;
    systolic: number | null;
    diastolic: number | null;
    phq9_score: number | null;
    created_at: string;
};

export type SleepLog = {
    uuid: string;
    duration_minutes: number;
    quality_score: number;
    quality_category: string | null;
    sleep_time: string | null;
    wake_time: string | null;
    sleep_date: string;
};

export type ChatHistory = {
    uuid: string;
    message: string;
    sender_type: 'user' | 'ai';
    sentiment_score: number | null;
    detected_emotion: string | null;
    is_flagged: boolean;
    created_at: string;
};

export type Patient = {
    uuid: string;
    name: string;
    email: string;
    role: UserRole;
    is_active: boolean;
    created_at: string;
    profile: UserProfile | null;
    latest_mental_status: MentalStatusLog | null;
    latest_wearable: WearableData | null;
    latest_screening: HealthScreening | null;
};

export type Consultation = {
    uuid: string;
    status: ConsultationStatus;
    notes: string | null;
    scheduled_at: string | null;
    started_at: string | null;
    ended_at: string | null;
    created_at: string;
    patient: Patient | null;
    medic: {
        uuid: string;
        name: string;
        email: string;
        role: UserRole;
        is_active: boolean;
        created_at: string;
        profile: UserProfile | null;
    } | null;
};

export type AppNotification = {
    uuid: string;
    title: string;
    message: string;
    type: NotificationType;
    is_read: boolean;
    action_data: Record<string, unknown> | null;
    created_at: string;
};

export type RiskThreshold = {
    id: number;
    uuid: string;
    parameter_name: string;
    low_min: number | null;
    low_max: number | null;
    medium_min: number | null;
    medium_max: number | null;
    high_min: number | null;
    high_max: number | null;
    weight: number;
    description: string | null;
    is_active: boolean;
};

export type PaginatedResource<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
};
