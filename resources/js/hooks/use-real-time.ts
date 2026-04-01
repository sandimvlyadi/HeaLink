import { useEffect } from 'react';

/**
 * Subscribe to real-time risk elevation alerts on the doctor's private channel.
 * Calls `onAlert` whenever a PatientRiskElevated event is broadcast.
 *
 * @param doctorUuid - UUID of the authenticated doctor
 * @param onAlert    - Callback invoked with the event payload on each alert
 */
export function useDoctorRiskAlerts(
    doctorUuid: string | null | undefined,
    onAlert: (payload: {
        patientUuid: string;
        riskLevel: string;
        riskScore: number;
    }) => void,
): void {
    useEffect(() => {
        if (!doctorUuid || !window.Echo) {
            return;
        }

        const channel = window.Echo.private(`doctor.${doctorUuid}`);

        channel.listen('.PatientRiskElevated', onAlert);

        return () => {
            channel.stopListening('.PatientRiskElevated');
            window.Echo?.leave(`doctor.${doctorUuid}`);
        };
    }, [doctorUuid]); // eslint-disable-line react-hooks/exhaustive-deps
}

/**
 * Subscribe to real-time notification alerts on the user's private channel.
 * Calls `onNotification` whenever a new notification is broadcast.
 *
 * @param userUuid       - UUID of the authenticated user
 * @param onNotification - Callback invoked with the event payload on each notification
 */
export function useNotificationAlerts(
    userUuid: string | null | undefined,
    onNotification: (payload: { title: string; body: string }) => void,
): void {
    useEffect(() => {
        if (!userUuid || !window.Echo) {
            return;
        }

        const channel = window.Echo.private(`doctor.${userUuid}`);

        channel.listen('.NewSentimentAlert', onNotification);

        return () => {
            channel.stopListening('.NewSentimentAlert');
            window.Echo?.leave(`doctor.${userUuid}`);
        };
    }, [userUuid]); // eslint-disable-line react-hooks/exhaustive-deps
}
