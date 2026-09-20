ALTER TABLE community_members
ADD UNIQUE KEY unique_community_member (community_id, user_id);
