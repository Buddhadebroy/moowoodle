import React, { useState, useEffect } from "react";
import axios from "axios";
import { getApiLink } from "../../services/apiService";
import ViewEnroll from "../ViewEnroll/ViewEnroll";

const MyGroups = () => {
  const [groups, setGroups] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [selectedItem, setSelectedItem] = useState(null); // Stores full item object with order_id

  useEffect(() => {
    fetchGroups();
  }, []);

  const fetchGroups = async () => {
    try {
      const response = await axios.post(getApiLink("get-user-groups"), {}, {
        headers: { "X-WP-Nonce": appLocalizer.nonce },
      });

      setGroups(response.data.groups);
      setLoading(false);
    } catch (error) {
      console.error("Error fetching groups:", error);
      setError("Failed to load groups.");
      setLoading(false);
    }
  };

  const handleViewEnroll = (group, item) => {
    // Combine item data with order_id from the parent group
    setSelectedItem({
      ...item,
      order_id: group.order_id
    });
  };

  if (loading) return <p>Loading groups...</p>;
  if (error) return <p>{error}</p>;

  // If an item is selected, render the ViewEnroll component with full item data including order_id
  if (selectedItem) {
    return <ViewEnroll item={selectedItem} onBack={() => setSelectedItem(null)} />;
  }

  return (
    <div>
      <h3>My Groups</h3>

      {groups.length > 0 ? (
        groups.map((group) => (
          <div key={group.group_id}>
            <h4>{group.group_name}</h4>
            <table>
              <thead>
                <tr>
                  <th>Course ID</th>
                  <th>Product ID</th>
                  <th>Total Quantity</th>
                  <th>Available Quantity</th>
                  <th>Status</th>
                  <th>Enroll User</th>
                </tr>
              </thead>
              <tbody>
                {group.items.map((item) => (
                  <tr key={item.id}>
                    <td>{item.course_id}</td>
                    <td>{item.product_id}</td>
                    <td>{item.total_quantity}</td>
                    <td>{item.available_quantity}</td>
                    <td>{item.status}</td>
                    <td>
                      <button
                        onClick={() => handleViewEnroll(group, item)} // Pass group and item
                        className="view-enroll-btn"
                      >
                        View Enroll
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ))
      ) : (
        <p>You have no groups yet.</p>
      )}
    </div>
  );
};

export default MyGroups;