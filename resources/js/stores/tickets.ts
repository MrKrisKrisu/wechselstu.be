import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Ticket } from '@/types';

export const useTicketStore = defineStore('tickets', () => {
    const tickets = ref<Ticket[]>([]);

    /** Tickets respecting the 1h-done filter */
    const visibleTickets = computed(() => {
        const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);

        return tickets.value.filter((t) => {
            if (t.status !== 'done') {
                return true;
            }

            if (!t.done_at) {
                return true;
            }

            return new Date(t.done_at) > oneHourAgo;
        });
    });

    const openTickets = computed(() =>
        visibleTickets.value.filter((t) => t.status === 'open'),
    );
    const acceptedTickets = computed(() =>
        visibleTickets.value.filter((t) => t.status === 'accepted'),
    );
    const doneTickets = computed(() =>
        tickets.value
            .filter((t) => t.status === 'done')
            .sort((a, b) => {
                const ta = a.done_at ?? a.created_at;
                const tb = b.done_at ?? b.created_at;
                return new Date(tb).getTime() - new Date(ta).getTime();
            })
            .slice(0, 20),
    );

    function setTickets(list: Ticket[]): void {
        tickets.value = list;
    }

    function addTicket(ticket: Ticket): void {
        const exists = tickets.value.findIndex((t) => t.id === ticket.id);

        if (exists === -1) {
            tickets.value.unshift(ticket);
        }
    }

    function updateTicket(ticket: Ticket): void {
        const idx = tickets.value.findIndex((t) => t.id === ticket.id);

        if (idx !== -1) {
            tickets.value[idx] = ticket;
        } else {
            tickets.value.unshift(ticket);
        }
    }

    return {
        tickets,
        visibleTickets,
        openTickets,
        acceptedTickets,
        doneTickets,
        setTickets,
        addTicket,
        updateTicket,
    };
});
