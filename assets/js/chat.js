document.addEventListener("DOMContentLoaded", () => {
    const chatMessages = document.getElementById("chatMessages");
    const chatForm = document.getElementById("chatForm");
    const messageInput = document.getElementById("chatMessageInput");
    const sendBtn = document.getElementById("chatSendBtn");
    const csrfToken = document.getElementById("csrfToken")?.value;
    const chatWithId = parseInt(document.getElementById("chatWithId")?.value || 0);
    const currentUserId = parseInt(document.getElementById("currentUserId")?.value || 0);
    let lastMessageId = 0;
    let pollingInterval = null;
    if (!chatMessages || !chatForm || chatWithId <= 0 || currentUserId <= 0) {
        return;
    }
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                "'": '&#39;',
                '"': '&quot;'
            }[tag] || tag)
        );
    }
    /**
     * Render message item inside chat container
     */
    function appendMessage(msg) {
        const isMe = parseInt(msg.sender_id) === currentUserId;
        const bubble = document.createElement("div");
        bubble.className = `message-bubble ${isMe ? 'message-sent' : 'message-received'}`;
        // Format time nicely
        const timeString = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        bubble.innerHTML = `
            <div class="message-text">${escapeHTML(msg.message)}</div>
            <span class="message-time">${timeString}</span>
        `;
        chatMessages.appendChild(bubble);
        lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
    }
    /**
     * Pull new messages from the API endpoint
     */
    async function fetchNewMessages() {
        try {
            const response = await fetch(`api/get_messages.php?chat_with=${chatWithId}&last_id=${lastMessageId}`);
            if (!response.ok) throw new Error("Network response error.");
            const data = await response.json();
            if (data.status === 'success') {
                if (data.messages && data.messages.length > 0) {
                    // Remove initial connecting message on first load
                    if (lastMessageId === 0) {
                        chatMessages.innerHTML = '';
                    }
                    data.messages.forEach(msg => appendMessage(msg));
                    scrollToBottom();
                } else if (lastMessageId === 0) {
                    // Empty conversation
                    chatMessages.innerHTML = '<div class="text-center text-muted small my-3">No messages yet. Say Namaste to start the conversation!</div>';
                }
            } else {
                console.error("API error:", data.message);
            }
        } catch (error) {
            console.error("Failed to load messages:", error);
        }
    }
    /**
     * Submit a new message
     */
    chatForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const messageText = messageInput.value.trim();
        if (!messageText) return;
        // Temporarily disable form controls
        messageInput.disabled = true;
        sendBtn.disabled = true;
        try {
            const formData = new FormData();
            formData.append("receiver_id", chatWithId);
            formData.append("message", messageText);
            formData.append("csrf_token", csrfToken);
            const response = await fetch("api/send_message.php", {
                method: "POST",
                body: formData
            });
            if (!response.ok) throw new Error("HTTP connection error.");
            const data = await response.json();
            if (data.status === 'success') {
                messageInput.value = "";
                // Pull updates immediately
                await fetchNewMessages();
            } else {
                alert(data.message || "Failed to send message.");
            }
        } catch (error) {
            console.error("Message send error:", error);
            alert("Connection error. Could not send your message.");
        } finally {
            messageInput.disabled = false;
            sendBtn.disabled = false;
            messageInput.focus();
        }
    });
    // Start initial load and interval polling loop
    fetchNewMessages();
    pollingInterval = setInterval(fetchNewMessages, 2500);
    // Clean up interval on page unload
    window.addEventListener("beforeunload", () => {
        if (pollingInterval) clearInterval(pollingInterval);
    });
});
